<?php

namespace Tests\Feature;

use App\Authorization\Roles;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JPayrollAttendance;
use App\Models\User;
use App\Notifications\EmployeeMonthlyReport;
use App\Notifications\ManagerMonthlyReport;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendMonthlyReportNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Department $department;

    protected User $managerUser;

    protected Employee $managerEmployee;

    protected User $employeeUser;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        // Setup department
        $this->department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
        ]);

        // Setup Manager User and Employee
        $this->managerUser = User::factory()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);
        $this->managerUser->assignRole(Roles::MANAGER);

        $this->managerEmployee = Employee::create([
            'employee_id' => 'MGR001',
            'user_id' => $this->managerUser->id,
            'first_name' => 'Jane',
            'last_name' => 'Manager',
            'email' => 'manager@example.com',
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);

        // Setup Employee User and Employee
        $this->employeeUser = User::factory()->create([
            'name' => 'Regular Employee',
            'email' => 'employee@example.com',
        ]);
        $this->employeeUser->assignRole(Roles::EMPLOYEE);

        $this->employee = Employee::create([
            'employee_id' => 'EMP001',
            'user_id' => $this->employeeUser->id,
            'first_name' => 'John',
            'last_name' => 'Employee',
            'email' => 'employee@example.com',
            'department_id' => $this->department->id,
            'status' => 'active',
        ]);
    }

    public function test_command_sends_manager_notifications(): void
    {
        // Add attendance data for employee in previous month
        $prevMonth = now()->subMonthNoOverflow();
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->managerUser,
            ManagerMonthlyReport::class,
            function (ManagerMonthlyReport $notification) {
                return $notification->departmentName === 'Engineering'
                    && $notification->attendanceSummaries->count() === 1
                    && $notification->attendanceSummaries->first()->employeeName === 'John Employee';
            }
        );
    }

    public function test_command_sends_employee_notifications(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->employeeUser,
            EmployeeMonthlyReport::class,
            function (EmployeeMonthlyReport $notification) {
                return $notification->attendanceSummary->employeeName === 'John Employee'
                    && $notification->attendanceSummary->totalDays === 1;
            }
        );
    }

    public function test_command_skips_employees_with_no_attendance_data(): void
    {
        Notification::fake();

        // No attendance data created for this month/prev month
        $this->artisan('reports:send-monthly')
            ->assertSuccessful();

        // Employee should NOT be notified
        Notification::assertNotSentTo($this->employeeUser, EmployeeMonthlyReport::class);

        // Manager should NOT be notified because department has no attendance summaries
        Notification::assertNotSentTo($this->managerUser, ManagerMonthlyReport::class);
    }

    public function test_command_dry_run_does_not_send_notifications(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly --dry-run')
            ->expectsOutputToContain('[DRY RUN] Would notify manager')
            ->expectsOutputToContain('[DRY RUN] Would notify employee')
            ->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_command_uses_custom_month_and_year(): void
    {
        // Add attendance data for custom month (e.g. May 2026)
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => '2026-05-15',
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly --month=5 --year=2026')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->employeeUser,
            EmployeeMonthlyReport::class,
            function (EmployeeMonthlyReport $notification) {
                return $notification->month->format('Y-m') === '2026-05';
            }
        );
    }

    public function test_notifications_have_correct_array_data(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
            'alpha' => 0,
            'telat' => 5,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->managerUser,
            ManagerMonthlyReport::class,
            function (ManagerMonthlyReport $notification) {
                $array = $notification->toArray($this->managerUser);
                $this->assertEquals('monthly_report', $array['type']);
                $this->assertEquals('Engineering', $array['department']);
                $this->assertCount(1, $array['sections']['attendance']['summaries']);

                $summary = $array['sections']['attendance']['summaries'][0];
                $this->assertEquals('John Employee', $summary['employee_name']);
                $this->assertEquals(1, $summary['late']);

                return true;
            }
        );

        Notification::assertSentTo(
            $this->employeeUser,
            EmployeeMonthlyReport::class,
            function (EmployeeMonthlyReport $notification) {
                $array = $notification->toArray($this->employeeUser);
                $this->assertEquals('monthly_report', $array['type']);

                $att = $array['sections']['attendance'];
                $this->assertEquals(1, $att['total']);
                $this->assertEquals(1, $att['late']);

                return true;
            }
        );
    }

    public function test_notification_mail_content(): void
    {
        $prevMonth = now()->subMonthNoOverflow();
        JPayrollAttendance::create([
            'employee_id' => $this->employee->id,
            'shift_date' => $prevMonth->copy()->startOfMonth()->toDateString(),
            'alpha' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
        ]);

        Notification::fake();

        $this->artisan('reports:send-monthly')
            ->assertSuccessful();

        Notification::assertSentTo(
            $this->managerUser,
            ManagerMonthlyReport::class,
            function (ManagerMonthlyReport $notification) {
                $mailMessage = $notification->toMail($this->managerUser);
                $this->assertStringContainsString('Monthly Attendance Report', $mailMessage->subject);
                $this->assertStringContainsString('Manager User', $mailMessage->greeting);
                $this->assertStringContainsString('Engineering', $mailMessage->introLines[0]);
                $this->assertStringContainsString('John Employee', $mailMessage->introLines[1]);

                return true;
            }
        );

        Notification::assertSentTo(
            $this->employeeUser,
            EmployeeMonthlyReport::class,
            function (EmployeeMonthlyReport $notification) {
                $mailMessage = $notification->toMail($this->employeeUser);
                $this->assertStringContainsString('Your Monthly Attendance Summary', $mailMessage->subject);
                $this->assertStringContainsString('Regular Employee', $mailMessage->greeting);
                $this->assertStringContainsString('Total Working Days', $mailMessage->introLines[1]);

                return true;
            }
        );
    }
}
