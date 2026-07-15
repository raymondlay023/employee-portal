<?php

namespace Database\Seeders;

use App\Authorization\Permissions;
use App\Authorization\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Define all core permissions
        $permissions = [
            Permissions::ACCESS_HR_PORTAL,
            Permissions::MANAGE_EMPLOYEES,
            Permissions::VIEW_ANY_ATTENDANCE,
            Permissions::MANAGE_ATTENDANCE,
            Permissions::MANAGE_LEAVES,
            Permissions::VIEW_API_LOGS,
        ];

        // Prune old obsolete permissions that are no longer defined
        Permission::whereNotIn('name', $permissions)->delete();

        // Use findOrCreate so this seeder can be run repeatedly without crashing
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // 3. Define Roles and their specific permissions mapping
        $rolePermissions = [
            Roles::ADMIN => [], // Admin gets everything automatically via Gate::before in AppServiceProvider

            Roles::HR => [
                Permissions::ACCESS_HR_PORTAL,
                Permissions::MANAGE_EMPLOYEES,
                Permissions::VIEW_ANY_ATTENDANCE,
                Permissions::MANAGE_ATTENDANCE,
                Permissions::MANAGE_LEAVES,
            ],

            Roles::MANAGER => [
                Permissions::ACCESS_HR_PORTAL,
                Permissions::MANAGE_LEAVES,
                Permissions::VIEW_ANY_ATTENDANCE,
            ],

            Roles::EMPLOYEE => [], // Employees have no special administrative permissions
        ];

        // 4. Process and sync permissions to roles safely
        foreach ($rolePermissions as $roleName => $permissionsToAssign) {
            $role = Role::findOrCreate($roleName);

            // syncPermissions ensures exact match, removing anything old and applying the new list
            $role->syncPermissions($permissionsToAssign);
        }
    }
}
