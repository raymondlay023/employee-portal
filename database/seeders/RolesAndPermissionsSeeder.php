<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use App\Authorization\Roles;
use App\Authorization\Permissions;

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
        ];

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