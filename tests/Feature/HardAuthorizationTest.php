<?php

namespace Tests\Feature;

use App\Authorization\Roles;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HardAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function createUserWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->assignRole($roleName);

        return $user;
    }

    private function createEmployeeForUser(User $user, Department $dept, ?string $nik = null): Employee
    {
        return Employee::factory()->create([
            'user_id' => $user->id,
            'department_id' => $dept->id,
            'employee_id' => $nik ?? 'NIK-'.fake()->unique()->numerify('####'),
        ]);
    }

    public function test_api_logs_access_control(): void
    {
        $admin = $this->createUserWithRole(Roles::ADMIN);
        $hr = $this->createUserWithRole(Roles::HR);
        $manager = $this->createUserWithRole(Roles::MANAGER);
        $employee = $this->createUserWithRole(Roles::EMPLOYEE);

        // Admin can access API logs
        $response = $this->actingAs($admin)->get(route('system.api-logs'));
        $response->assertStatus(200);

        // HR is denied (403)
        $response = $this->actingAs($hr)->get(route('system.api-logs'));
        $response->assertStatus(403);

        // Manager is denied (403)
        $response = $this->actingAs($manager)->get(route('system.api-logs'));
        $response->assertStatus(403);

        // Employee is denied (403)
        $response = $this->actingAs($employee)->get(route('system.api-logs'));
        $response->assertStatus(403);
    }

    public function test_manager_department_scoping_for_leave_approvals(): void
    {
        $deptA = Department::create(['name' => 'Dept A']);
        $deptB = Department::create(['name' => 'Dept B']);

        $managerUser = $this->createUserWithRole(Roles::MANAGER);
        $this->createEmployeeForUser($managerUser, $deptA);

        $employeeUserA = $this->createUserWithRole(Roles::EMPLOYEE);
        $this->createEmployeeForUser($employeeUserA, $deptA);

        $employeeUserB = $this->createUserWithRole(Roles::EMPLOYEE);
        $this->createEmployeeForUser($employeeUserB, $deptB);

        // Create leave requests
        $leaveA = LeaveRequest::create([
            'user_id' => $employeeUserA->id,
            'type' => 'annual',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Holiday A',
            'status' => 'pending',
        ]);

        $leaveB = LeaveRequest::create([
            'user_id' => $employeeUserB->id,
            'type' => 'annual',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Holiday B',
            'status' => 'pending',
        ]);

        // Manager can view detail of employee in same department
        $response = $this->actingAs($managerUser)->get(route('leave-requests.show', $leaveA));
        $response->assertStatus(200);

        // Manager cannot view detail of employee in different department
        $response = $this->actingAs($managerUser)->get(route('leave-requests.show', $leaveB));
        $response->assertStatus(403);

        // Manager can approve employee in same department
        $response = $this->actingAs($managerUser)->post(route('leave-requests.approve', $leaveA));
        $response->assertStatus(302); // Redirect back
        $this->assertEquals('approved', $leaveA->fresh()->status);

        // Manager cannot approve employee in different department
        $response = $this->actingAs($managerUser)->post(route('leave-requests.approve', $leaveB));
        $response->assertStatus(403);
        $this->assertEquals('pending', $leaveB->fresh()->status);

        // Reset leave status to pending for reject test
        $leaveA->refresh();
        $leaveA->update(['status' => 'pending']);

        // Manager can reject employee in same department
        $response = $this->actingAs($managerUser)->post(route('leave-requests.reject', $leaveA));
        $response->assertStatus(302);
        $this->assertEquals('rejected', $leaveA->fresh()->status);

        // Manager cannot reject employee in different department
        $response = $this->actingAs($managerUser)->post(route('leave-requests.reject', $leaveB));
        $response->assertStatus(403);
        $this->assertEquals('pending', $leaveB->fresh()->status);
    }

    public function test_leave_request_ownership_authorization(): void
    {
        $dept = Department::create(['name' => 'Dept']);

        $userA = $this->createUserWithRole(Roles::EMPLOYEE);
        $this->createEmployeeForUser($userA, $dept);

        $userB = $this->createUserWithRole(Roles::EMPLOYEE);
        $this->createEmployeeForUser($userB, $dept);

        $leaveA = LeaveRequest::create([
            'user_id' => $userA->id,
            'type' => 'annual',
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
            'reason' => 'Holiday A',
            'status' => 'pending',
        ]);

        // User B cannot edit User A's leave request
        $response = $this->actingAs($userB)->get(route('leave-requests.edit', $leaveA));
        $response->assertStatus(403);

        // User B cannot update User A's leave request
        $response = $this->actingAs($userB)->put(route('leave-requests.update', $leaveA), [
            'type' => 'annual',
            'start_date' => now()->addDays(6)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'reason' => 'Changed',
        ]);
        $response->assertStatus(403);

        // User B cannot delete User A's leave request
        $response = $this->actingAs($userB)->delete(route('leave-requests.destroy', $leaveA));
        $response->assertStatus(403);
    }

    public function test_attendance_department_scoping_for_managers(): void
    {
        $deptA = Department::create(['name' => 'Dept A']);
        $deptB = Department::create(['name' => 'Dept B']);

        $managerUser = $this->createUserWithRole(Roles::MANAGER);
        $managerEmployee = $this->createEmployeeForUser($managerUser, $deptA);

        $employeeUserA = $this->createUserWithRole(Roles::EMPLOYEE);
        $employeeA = $this->createEmployeeForUser($employeeUserA, $deptA);

        $employeeUserB = $this->createUserWithRole(Roles::EMPLOYEE);
        $employeeB = $this->createEmployeeForUser($employeeUserB, $deptB);

        // Manager can view attendance of employee in same department
        $response = $this->actingAs($managerUser)->get(route('attendance.index', ['employee_id' => $employeeA->id]));
        $response->assertStatus(200);

        // Manager cannot view attendance of employee in different department
        $response = $this->actingAs($managerUser)->get(route('attendance.index', ['employee_id' => $employeeB->id]));
        $response->assertStatus(403);

        // Employee cannot view attendance of another employee
        $response = $this->actingAs($employeeUserA)->get(route('attendance.index', ['employee_id' => $employeeB->id]));
        $response->assertStatus(403);
    }
}
