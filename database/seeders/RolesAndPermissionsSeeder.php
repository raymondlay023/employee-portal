<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 2. Define all core permissions
        $permissions = [
            'view employees',
            'manage employees',
            'view attendance',
            'manage attendance',
            'sync attendance',
            'view leaves',
            'manage leaves',
            'view daily logs',
        ];

        // Use findOrCreate so this seeder can be run repeatedly without crashing
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // 3. Define Roles and their specific permissions mapping
        $rolePermissions = [
            'Admin' => Permission::all(), // Gets everything automatically
            
            'HR' => [
                'view employees',
                'manage employees',
                'view attendance',
                'manage attendance',
                'sync attendance',
                'view leaves',
                'manage leaves',
                'view daily logs',
            ],
            
            'Manager' => [
                'view daily logs',
                'view employees',
                'view attendance',
                'view leaves',
                'manage leaves',
            ],
            
            'Employee' => [
                'view daily logs',
                'view attendance'
            ],
        ];

        // 4. Process and sync permissions to roles safely
        foreach ($rolePermissions as $roleName => $permissionsToAssign) {
            $role = Role::findOrCreate($roleName);
            
            // syncPermissions ensures exact match, removing anything old and applying the new list
            $role->syncPermissions($permissionsToAssign);
        }
    }
}