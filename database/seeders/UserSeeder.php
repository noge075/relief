<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\RoleType;
use App\Models\Department;
use App\Models\HomeOfficePolicy;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $itDept = Department::firstOrCreate(['name' => 'IT']);
        $hrDept = Department::firstOrCreate(['name' => 'HR']);
        $salesDept = Department::firstOrCreate(['name' => 'Sales']);

        $standardSchedule = WorkSchedule::firstOrCreate(
            ['name' => 'Standard (40h)'],
            [
                'weekly_pattern' => ['monday' => 8, 'tuesday' => 8, 'wednesday' => 8, 'thursday' => 8, 'friday' => 8, 'saturday' => 0, 'sunday' => 0],
                'start_time' => '08:00:00',
                'end_time' => '16:30:00',
            ]
        );

        $studentSchedule = WorkSchedule::firstOrCreate(
            ['name' => 'Student (20h)'],
            [
                'weekly_pattern' => ['monday' => 4, 'tuesday' => 4, 'wednesday' => 4, 'thursday' => 4, 'friday' => 4, 'saturday' => 0, 'sunday' => 0],
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
            ]
        );

        $fullRemotePolicy = HomeOfficePolicy::where('type', 'full_remote')->first();
        $flexiblePolicy = HomeOfficePolicy::where('type', 'flexible')->first();
        $limitedPolicy = HomeOfficePolicy::where('type', 'limited')->first();
        $noHoPolicy = HomeOfficePolicy::where('type', 'none')->first();

        // 1. Super Admin
        $admin = User::firstOrCreate(['email' => 'admin@oe.hu'], [
            'last_name' => 'Super', 'first_name' => 'Admin', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD, 'work_schedule_id' => $standardSchedule->id,
            'home_office_policy_id' => $flexiblePolicy->id, 'hired_at' => now()->subYears(5),
        ]);
        $admin->syncRoles(RoleType::SUPER_ADMIN->value);
        $admin->departments()->sync([$itDept->id, $hrDept->id]);

        // 2. HR User
        $hr = User::firstOrCreate(['email' => 'hr@oe.hu'], [
            'last_name' => 'HR', 'first_name' => 'Hanna', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD, 'work_schedule_id' => $standardSchedule->id,
            'home_office_policy_id' => $fullRemotePolicy->id, 'hired_at' => now()->subYears(3),
        ]);
        $hr->syncRoles(RoleType::HR->value);
        $hr->departments()->sync([$hrDept->id]);

        // 3. Manager
        $manager = User::firstOrCreate(['email' => 'manager@oe.hu'], [
            'last_name' => 'Menedzser', 'first_name' => 'Márton', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD, 'work_schedule_id' => $standardSchedule->id,
            'home_office_policy_id' => $flexiblePolicy->id, 'hired_at' => now()->subYears(4),
        ]);
        $manager->syncRoles(RoleType::MANAGER->value);
        $manager->departments()->sync([$itDept->id]);

        // 4. Employee (Standard, Limited HO)
        $employee = User::firstOrCreate(['email' => 'employee@oe.hu'], [
            'last_name' => 'Munkavállaló', 'first_name' => 'Mihály', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD, 'manager_id' => $manager->id,
            'work_schedule_id' => $standardSchedule->id, 'home_office_policy_id' => $limitedPolicy->id,
            'hired_at' => now()->subYears(1),
        ]);
        $employee->syncRoles(RoleType::EMPLOYEE->value);
        $employee->departments()->sync([$itDept->id]);

        // 5. Payroll User
        $payroll = User::firstOrCreate(['email' => 'payroll@oe.hu'], [
            'last_name' => 'Bérszámfejtő', 'first_name' => 'Béla', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD, 'work_schedule_id' => $standardSchedule->id,
            'home_office_policy_id' => $noHoPolicy->id, 'hired_at' => now()->subYears(2),
        ]);
        $payroll->syncRoles(RoleType::PAYROLL->value);
        $payroll->departments()->sync([$hrDept->id]);

        // 6. Hourly Employee
        $hourly = User::firstOrCreate(['email' => 'hourly@oe.hu'], [
            'last_name' => 'Órabéres', 'first_name' => 'Olga', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::HOURLY, 'manager_id' => $manager->id,
            'work_schedule_id' => $standardSchedule->id, 'home_office_policy_id' => $limitedPolicy->id,
            'hired_at' => now()->subMonths(6),
        ]);
        $hourly->syncRoles(RoleType::EMPLOYEE->value);
        $hourly->departments()->sync([$itDept->id]);

        // 7. Fixed Contract Employee
        $fixed = User::firstOrCreate(['email' => 'fixed@oe.hu'], [
            'last_name' => 'Fix-szerződéses', 'first_name' => 'Ferenc', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::FIXED, 'manager_id' => $manager->id,
            'work_schedule_id' => $standardSchedule->id, 'home_office_policy_id' => $noHoPolicy->id,
            'hired_at' => now()->subMonths(8),
        ]);
        $fixed->syncRoles(RoleType::EMPLOYEE->value);
        $fixed->departments()->sync([$salesDept->id]);

        // 8. Student Employee
        $student = User::firstOrCreate(['email' => 'student@oe.hu'], [
            'last_name' => 'Diák', 'first_name' => 'Dóra', 'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STUDENT, 'manager_id' => $manager->id,
            'work_schedule_id' => $studentSchedule->id, 'home_office_policy_id' => $limitedPolicy->id,
            'hired_at' => now()->subMonths(3),
        ]);
        $student->syncRoles(RoleType::EMPLOYEE->value);
        $student->departments()->sync([$itDept->id]);
    }
}