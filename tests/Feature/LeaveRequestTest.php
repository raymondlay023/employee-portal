<?php

namespace Tests\Feature;

use App\Authorization\Roles;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createEmployeeUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::EMPLOYEE);
        Employee::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    private function createHRUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Roles::HR);
        Employee::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_employee_can_view_only_own_leave_requests(): void
    {
        $employee1 = $this->createEmployeeUser();
        $employee2 = $this->createEmployeeUser();

        $leave1 = LeaveRequest::create([
            'user_id' => $employee1->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $leave2 = LeaveRequest::create([
            'user_id' => $employee2->id,
            'type' => 'sick',
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-05',
            'reason' => 'Doctor appointment',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($employee1)->get(route('leave-requests.index'));

        $response->assertStatus(200);
        $response->assertSee('Vacation');
        $response->assertDontSee('Doctor appointment');
    }

    public function test_hr_can_view_all_leave_requests_with_scope_company(): void
    {
        $employee = $this->createEmployeeUser();
        $hr = $this->createHRUser();

        LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Employee Vacation',
            'status' => 'pending',
        ]);

        LeaveRequest::create([
            'user_id' => $hr->id,
            'type' => 'sick',
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-05',
            'reason' => 'HR Doctor appointment',
            'status' => 'pending',
        ]);

        // Company scope should see both
        $response = $this->actingAs($hr)->get(route('leave-requests.index', ['scope' => 'company']));
        $response->assertStatus(200);
        $response->assertSee('Employee Vacation');
        $response->assertSee('HR Doctor appointment');

        // Personal scope should only see HR's own request
        $response = $this->actingAs($hr)->get(route('leave-requests.index', ['scope' => 'personal']));
        $response->assertStatus(200);
        $response->assertDontSee('Employee Vacation');
        $response->assertSee('HR Doctor appointment');
    }

    public function test_employee_can_submit_leave_request(): void
    {
        $employee = $this->createEmployeeUser();

        $response = $this->actingAs($employee)->post(route('leave-requests.store'), [
            'type' => 'annual',
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-12',
            'reason' => 'Family event',
        ]);

        $response->assertRedirect(route('leave-requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'user_id' => $employee->id,
            'type' => 'annual',
            'reason' => 'Family event',
            'status' => 'pending',
        ]);
    }

    public function test_employee_can_edit_pending_leave_request(): void
    {
        $employee = $this->createEmployeeUser();
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Original Reason',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($employee)->put(route('leave-requests.update', $leave), [
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Updated Reason',
        ]);

        $response->assertRedirect(route('leave-requests.index'));
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'reason' => 'Updated Reason',
        ]);
    }

    public function test_employee_cannot_edit_non_pending_leave_request(): void
    {
        $employee = $this->createEmployeeUser();
        $leave = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Original Reason',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($employee)->put(route('leave-requests.update', $leave), [
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Updated Reason',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'reason' => 'Original Reason',
        ]);
    }

    public function test_hr_can_approve_and_reject_leave_requests(): void
    {
        $employee = $this->createEmployeeUser();
        $hr = $this->createHRUser();

        $leave1 = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $leave2 = LeaveRequest::create([
            'user_id' => $employee->id,
            'type' => 'sick',
            'start_date' => '2026-07-05',
            'end_date' => '2026-07-05',
            'reason' => 'Sick Day',
            'status' => 'pending',
        ]);

        // Approve leave 1
        $response = $this->actingAs($hr)->post(route('leave-requests.approve', $leave1));
        $response->assertRedirect();
        $this->assertEquals('approved', $leave1->fresh()->status);

        // Reject leave 2
        $response = $this->actingAs($hr)->post(route('leave-requests.reject', $leave2));
        $response->assertRedirect();
        $this->assertEquals('rejected', $leave2->fresh()->status);
    }

    public function test_non_hr_cannot_approve_or_reject_leave_requests(): void
    {
        $employee1 = $this->createEmployeeUser();
        $employee2 = $this->createEmployeeUser();

        $leave = LeaveRequest::create([
            'user_id' => $employee1->id,
            'type' => 'annual',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-03',
            'reason' => 'Vacation',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($employee2)->post(route('leave-requests.approve', $leave));
        $response->assertStatus(403);
        $this->assertEquals('pending', $leave->fresh()->status);
    }
}
