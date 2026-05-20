<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create core permissions
        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'manage employees']);
        Permission::create(['name' => 'view attendance']);
        Permission::create(['name' => 'manage attendance']);
        Permission::create(['name' => 'view leaves']);
        Permission::create(['name' => 'manage leaves']);

        // Create roles and assign created permissions

        // Admin: Gets all permissions
        $roleAdmin = Role::create(['name' => 'Admin']);
        $roleAdmin->givePermissionTo(Permission::all());

        // HR: Can manage everything HR related
        $roleHR = Role::create(['name' => 'HR']);
        $roleHR->givePermissionTo([
            'view employees',
            'manage employees',
            'view attendance',
            'manage attendance',
            'view leaves',
            'manage leaves'
        ]);

        // Manager: Can view team data and approve leaves
        $roleManager = Role::create(['name' => 'Manager']);
        $roleManager->givePermissionTo([
            'view employees',
            'view attendance',
            'view leaves',
            'manage leaves'
        ]);

        // Employee: Basic access
        $roleEmployee = Role::create(['name' => 'Employee']);
        $roleEmployee->givePermissionTo([
            'view employees',
            'view attendance',
            'view leaves'
        ]);
    }
}
