<?php

namespace Tests\Feature;

use App\Authorization\Permissions;
use App\Authorization\Roles;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Run the seeder to set up roles and permissions
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_super_admin_has_all_permissions_implicitly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Roles::ADMIN);

        // Even though Admin has no explicit permissions in the DB, the Gate::before hook should grant them
        $this->assertTrue($admin->can(Permissions::ACCESS_HR_PORTAL));
        $this->assertTrue($admin->can(Permissions::MANAGE_EMPLOYEES));
        $this->assertTrue($admin->can(Permissions::VIEW_ANY_ATTENDANCE));
        $this->assertTrue($admin->can(Permissions::MANAGE_ATTENDANCE));
        $this->assertTrue($admin->can(Permissions::MANAGE_LEAVES));
        $this->assertTrue($admin->can('non-existent-permission-arbitrary-string'));
    }

    public function test_hr_has_correct_permissions(): void
    {
        $hr = User::factory()->create();
        $hr->assignRole(Roles::HR);

        $this->assertTrue($hr->can(Permissions::ACCESS_HR_PORTAL));
        $this->assertTrue($hr->can(Permissions::MANAGE_EMPLOYEES));
        $this->assertTrue($hr->can(Permissions::VIEW_ANY_ATTENDANCE));
        $this->assertTrue($hr->can(Permissions::MANAGE_ATTENDANCE));
        $this->assertTrue($hr->can(Permissions::MANAGE_LEAVES));
        $this->assertTrue($hr->can(Permissions::VIEW_ANY_WORK_LOGS));

        // Should not have arbitrary permissions
        $this->assertFalse($hr->can('non-existent-permission'));
    }

    public function test_manager_has_restricted_permissions(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(Roles::MANAGER);

        $this->assertTrue($manager->can(Permissions::ACCESS_HR_PORTAL));
        $this->assertTrue($manager->can(Permissions::MANAGE_LEAVES));
        $this->assertTrue($manager->can(Permissions::VIEW_ANY_ATTENDANCE));
        $this->assertTrue($manager->can(Permissions::VIEW_ANY_WORK_LOGS));

        // Managers should not be able to manage employees or attendance configurations
        $this->assertFalse($manager->can(Permissions::MANAGE_EMPLOYEES));
        $this->assertFalse($manager->can(Permissions::MANAGE_ATTENDANCE));
    }

    public function test_employee_has_no_administrative_permissions(): void
    {
        $employee = User::factory()->create();
        $employee->assignRole(Roles::EMPLOYEE);

        $this->assertFalse($employee->can(Permissions::ACCESS_HR_PORTAL));
        $this->assertFalse($employee->can(Permissions::MANAGE_EMPLOYEES));
        $this->assertFalse($employee->can(Permissions::VIEW_ANY_ATTENDANCE));
        $this->assertFalse($employee->can(Permissions::MANAGE_ATTENDANCE));
        $this->assertFalse($employee->can(Permissions::MANAGE_LEAVES));
        $this->assertFalse($employee->can(Permissions::VIEW_ANY_WORK_LOGS));
    }
}
