<?php

namespace Database\Seeders;

use App\Enums\PermissionType;
use App\Enums\RoleType;
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
        $permissions = PermissionType::cases();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission->value]);
        }

        Role::firstOrCreate(['name' => RoleType::SUPER_ADMIN->value]);

        // HR - Human Resources
        $hr = Role::firstOrCreate(['name' => RoleType::HR->value]);
        $hr->givePermissionTo([
            PermissionType::VIEW_USERS->value, 
            PermissionType::VIEW_ALL_USERS->value, 
            PermissionType::CREATE_USERS->value, 
            PermissionType::EDIT_USERS->value, 
            PermissionType::DELETE_USERS->value, 
            PermissionType::RESTORE_USERS->value, 
            PermissionType::VIEW_ANY_USER_PROFILE->value,
            PermissionType::MANAGE_USER_DOCUMENTS->value,
            PermissionType::MANAGE_DEPARTMENTS->value,
            PermissionType::MANAGE_WORK_SCHEDULES->value, 
            PermissionType::ASSIGN_WORK_SCHEDULES->value,
            PermissionType::VIEW_LEAVE_BALANCES->value, 
            PermissionType::VIEW_ALL_LEAVE_BALANCES->value, 
            PermissionType::ADJUST_LEAVE_BALANCES->value, 
            PermissionType::VIEW_LEAVE_REQUESTS->value, 
            PermissionType::VIEW_ALL_LEAVE_REQUESTS->value, 
            PermissionType::VIEW_LEAVE_REQUEST_DETAILS->value, 
            PermissionType::CREATE_LEAVE_REQUESTS->value,
            PermissionType::CREATE_PAST_LEAVE_REQUESTS->value,
            PermissionType::APPROVE_LEAVE_REQUESTS->value, 
            PermissionType::DELETE_LEAVE_REQUESTS->value,
            PermissionType::VIEW_ATTENDANCE->value, 
            PermissionType::MANAGE_ATTENDANCE->value, 
            PermissionType::EXPORT_ATTENDANCE->value, 
            PermissionType::VIEW_STATUS_BOARD->value,
            PermissionType::VIEW_DOCUMENTS->value, 
            PermissionType::UPLOAD_DOCUMENTS->value, 
            PermissionType::DELETE_DOCUMENTS->value,
            PermissionType::MANAGE_SETTINGS->value,
            PermissionType::VIEW_AUDIT_LOGS->value,
            PermissionType::SEND_BULK_EMAILS->value,
            PermissionType::MANAGE_HOME_OFFICE_POLICIES->value,
        ]);

        // Manager - Team Lead
        $manager = Role::firstOrCreate(['name' => RoleType::MANAGER->value]);
        $manager->givePermissionTo([
            PermissionType::VIEW_USERS->value,
            PermissionType::VIEW_ANY_USER_PROFILE->value,
            PermissionType::VIEW_LEAVE_REQUESTS->value, 
            PermissionType::CREATE_LEAVE_REQUESTS->value,
            PermissionType::APPROVE_LEAVE_REQUESTS->value,
            PermissionType::VIEW_ATTENDANCE->value, 
            PermissionType::VIEW_STATUS_BOARD->value,
            PermissionType::VIEW_DOCUMENTS->value,
            PermissionType::SEND_BULK_EMAILS->value,
        ]);

        // Employee - Standard User
        $employee = Role::firstOrCreate(['name' => RoleType::EMPLOYEE->value]);
        $employee->givePermissionTo([
            PermissionType::CREATE_LEAVE_REQUESTS->value,
        ]);

        // Payroll - Finance
        $payroll = Role::firstOrCreate(['name' => RoleType::PAYROLL->value]);
        $payroll->givePermissionTo([
            PermissionType::VIEW_USERS->value, 
            PermissionType::VIEW_ALL_USERS->value,
            PermissionType::VIEW_ATTENDANCE->value, 
            PermissionType::EXPORT_ATTENDANCE->value, 
            PermissionType::VIEW_STATUS_BOARD->value,
            PermissionType::VIEW_PAYROLL_DATA->value, 
            PermissionType::MANAGE_MONTHLY_CLOSURES->value,
            PermissionType::VIEW_ALL_LEAVE_REQUESTS->value,
            PermissionType::SEND_BULK_EMAILS->value,
        ]);
    }
}
