<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define Permissions
        $permissions = [
            // User / Employee Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'restore users', // Soft delete esetén
            'view any user profile', // HR/Manager láthatja másét

            // Department Management
            'manage departments', // Create, edit, delete

            // Work Schedule Management
            'manage work schedules', // Create, edit, delete schedules
            'assign work schedules',

            // Leave Management
            'view leave balances',
            'adjust leave balances', // HR only
            'view leave requests',
            'create leave requests', // Employee
            'approve leave requests', // Manager/HR
            'delete leave requests',

            // Attendance / Timesheet
            'view attendance',
            'manage attendance', // Edit logs manually
            'export attendance',

            // Documents
            'view documents',
            'upload documents',
            'delete documents',

            // Payroll / Monthly Closure
            'view payroll data',
            'manage monthly closures',
            
            // System / Settings
            'view audit logs',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // 2. Define Roles and Assign Permissions

        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all()); // Optional if using Gate::before

        // HR - Human Resources
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->givePermissionTo([
            'view users', 'create users', 'edit users', 'delete users', 'restore users', 'view any user profile',
            'manage departments',
            'manage work schedules', 'assign work schedules',
            'view leave balances', 'adjust leave balances', 'view leave requests', 'approve leave requests', 'delete leave requests',
            'view attendance', 'manage attendance', 'export attendance',
            'view documents', 'upload documents', 'delete documents',
        ]);

        // Manager - Team Lead
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->givePermissionTo([
            'view users', // Can view list (usually filtered by scope)
            'view any user profile', // Can view subordinates
            'view leave requests', 'approve leave requests',
            'view attendance',
            'view documents',
        ]);

        // Employee - Standard User
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->givePermissionTo([
            'create leave requests',
            // 'view own profile' is usually default logic, not permission
        ]);

        // Payroll - Finance
        $payroll = Role::firstOrCreate(['name' => 'payroll']);
        $payroll->givePermissionTo([
            'view users',
            'view attendance', 'export attendance',
            'view payroll data', 'manage monthly closures',
        ]);
    }
}
