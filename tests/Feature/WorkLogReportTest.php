<?php

namespace Tests\Feature;

use App\Authorization\Permissions;
use App\Authorization\Roles;
use App\Livewire\Hr\WorkLogReport;
use App\Models\DailyWorkLog;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkLogReportTest extends TestCase
{
    use RefreshDatabase;

    protected Department $deptA;

    protected Department $deptB;

    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(RolesAndPermissionsSeeder::class);

        // Seed core test departments/designations
        $this->deptA = Department::create(['name' => 'Department A', 'code' => 'DEPTA', 'description' => 'Dept A']);
        $this->deptB = Department::create(['name' => 'Department B', 'code' => 'DEPTB', 'description' => 'Dept B']);
        $this->designation = Designation::create(['title' => 'Software Engineer', 'description' => 'Engineer']);
    }

    /**
     * Helper to create an employee with user account.
     */
    private function createEmployee(string $firstName, Department $dept, ?string $role = null): User
    {
        $user = User::factory()->create([
            'name' => $firstName.' User',
        ]);

        if ($role) {
            $user->assignRole($role);
        }

        Employee::factory()->create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => 'Test',
            'department_id' => $dept->id,
            'designation_id' => $this->designation->id,
            'email' => $user->email,
        ]);

        return $user;
    }

    public function test_work_log_report_page_requires_authentication(): void
    {
        $response = $this->get('/work-log-report');
        $response->assertRedirect('/login');
    }

    public function test_employee_cannot_access_work_log_report(): void
    {
        $employee = $this->createEmployee('John', $this->deptA, Roles::EMPLOYEE);

        $response = $this->actingAs($employee)->get('/work-log-report');
        $response->assertStatus(403);
    }

    public function test_manager_can_access_work_log_report(): void
    {
        $manager = $this->createEmployee('ManagerA', $this->deptA, Roles::MANAGER);

        $response = $this->actingAs($manager)->get('/work-log-report');
        $response->assertOk()
            ->assertSeeLivewire('hr.work-log-report');
    }

    public function test_hr_can_access_work_log_report(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);

        $response = $this->actingAs($hr)->get('/work-log-report');
        $response->assertOk()
            ->assertSeeLivewire('hr.work-log-report');
    }

    public function test_admin_can_access_work_log_report(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMIN);

        $response = $this->actingAs($admin)->get('/work-log-report');
        $response->assertOk()
            ->assertSeeLivewire('hr.work-log-report');
    }

    public function test_manager_sees_only_own_department_employees(): void
    {
        $manager = $this->createEmployee('ManagerA', $this->deptA, Roles::MANAGER);
        $empDeptA = $this->createEmployee('EmployeeA', $this->deptA, Roles::EMPLOYEE);
        $empDeptB = $this->createEmployee('EmployeeB', $this->deptB, Roles::EMPLOYEE);

        $this->actingAs($manager);

        Livewire::test(WorkLogReport::class)
            ->assertSee('EmployeeA')
            ->assertDontSee('EmployeeB');
    }

    public function test_hr_sees_all_departments(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $empDeptA = $this->createEmployee('EmployeeA', $this->deptA, Roles::EMPLOYEE);
        $empDeptB = $this->createEmployee('EmployeeB', $this->deptB, Roles::EMPLOYEE);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->assertSee('EmployeeA')
            ->assertSee('EmployeeB');
    }

    public function test_hr_can_filter_by_department(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $empDeptA = $this->createEmployee('EmployeeA', $this->deptA, Roles::EMPLOYEE);
        $empDeptB = $this->createEmployee('EmployeeB', $this->deptB, Roles::EMPLOYEE);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('department_id', $this->deptB->id)
            ->assertSee('EmployeeB')
            ->assertDontSee('EmployeeA');
    }

    public function test_search_filters_employees_by_name(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $empA = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);
        $empB = $this->createEmployee('Bob', $this->deptA, Roles::EMPLOYEE);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('search', 'Alice')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    }

    public function test_day_view_mode_shows_single_date(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $emp = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-15',
            'start_time' => '08:00',
            'end_time' => '10:00', // 2.0 hours
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-16',
            'start_time' => '08:00',
            'end_time' => '12:00', // 4.0 hours (other date)
        ]);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('viewMode', 'day')
            ->set('date', '2026-07-15')
            ->assertSee('2h');
    }

    public function test_week_view_mode_shows_full_week(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $emp = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);

        // 2026-07-15 is Wednesday. Start of week is Sunday 2026-07-12
        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-13', // Monday
            'start_time' => '08:00',
            'end_time' => '10:00', // 2.0 hours
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-15', // Wednesday
            'start_time' => '08:00',
            'end_time' => '11:00', // 3.0 hours
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-20', // Next Monday (different week)
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('viewMode', 'week')
            ->set('date', '2026-07-15')
            ->assertSee('5h'); // 2 + 3 = 5 hours
    }

    public function test_month_view_mode_shows_full_month(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $emp = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-01',
            'start_time' => '08:00',
            'end_time' => '10:00', // 2.0h
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-31',
            'start_time' => '08:00',
            'end_time' => '11:30', // 3.5h
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-08-01', // Different month
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('viewMode', 'month')
            ->set('month', '7')
            ->set('year', '2026')
            ->assertSee('5.5h'); // 2.0 + 3.5 = 5.5 hours
    }

    public function test_range_view_mode_shows_date_range(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $emp = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-10',
            'start_time' => '08:00',
            'end_time' => '10:00', // 2.0h
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-15',
            'start_time' => '08:00',
            'end_time' => '11:00', // 3.0h
        ]);

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-20', // Out of range
            'start_time' => '08:00',
            'end_time' => '12:00',
        ]);

        $this->actingAs($hr);

        Livewire::test(WorkLogReport::class)
            ->set('viewMode', 'range')
            ->set('startDate', '2026-07-10')
            ->set('endDate', '2026-07-15')
            ->assertSee('5h');
    }

    public function test_detail_page_requires_permission(): void
    {
        $emp = $this->createEmployee('John', $this->deptA, Roles::EMPLOYEE);
        $employeeRecord = Employee::where('user_id', $emp->id)->firstOrFail();

        $response = $this->actingAs($emp)->get("/work-log-report/{$employeeRecord->id}");
        $response->assertStatus(403);
    }

    public function test_manager_cannot_view_other_department_detail(): void
    {
        $manager = $this->createEmployee('ManagerA', $this->deptA, Roles::MANAGER);
        $empDeptB = $this->createEmployee('EmployeeB', $this->deptB, Roles::EMPLOYEE);
        $employeeRecord = Employee::where('user_id', $empDeptB->id)->firstOrFail();

        $response = $this->actingAs($manager)->get("/work-log-report/{$employeeRecord->id}");
        $response->assertStatus(403);
    }

    public function test_detail_page_renders_employee_work_logs(): void
    {
        $hr = $this->createEmployee('HROfficer', $this->deptA, Roles::HR);
        $emp = $this->createEmployee('Alice', $this->deptA, Roles::EMPLOYEE);
        $employeeRecord = Employee::where('user_id', $emp->id)->firstOrFail();

        DailyWorkLog::factory()->create([
            'user_id' => $emp->id,
            'date' => '2026-07-15',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'activity' => 'Coding Features',
            'remarks' => 'Doing some refactoring',
        ]);

        $this->actingAs($hr);

        $response = $this->get("/work-log-report/{$employeeRecord->id}?viewMode=day&date=2026-07-15");
        $response->assertOk()
            ->assertSee('Coding Features')
            ->assertSee('Doing some refactoring');
    }
}
