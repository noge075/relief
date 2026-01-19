<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\RoleType;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get Departments (from DepartmentSeeder)
        // Ha nem léteznek, létrehozzuk őket (fallback)
        $itDept = Department::firstOrCreate(['name' => 'IT Fejlesztés']);
        $hrDept = Department::firstOrCreate(['name' => 'HR & Payroll']);
        $salesDept = Department::firstOrCreate(['name' => 'Értékesítés']);
        // $mgmtDept = Department::firstOrCreate(['name' => 'Vezetőség']); // Ha kell

        // 2. Get Work Schedules (from WorkScheduleSeeder)
        $standardSchedule = WorkSchedule::where('name', 'Standard H-P 8ó')->first();
        $studentSchedule = WorkSchedule::where('name', 'Diák Kedd-Csütörtök')->first();
        
        if (!$standardSchedule) {
            $standardSchedule = WorkSchedule::create([
                'name' => 'Standard H-P 8ó',
                'weekly_pattern' => ['monday' => 8, 'tuesday' => 8, 'wednesday' => 8, 'thursday' => 8, 'friday' => 8, 'saturday' => 0, 'sunday' => 0]
            ]);
        }

        // 3. Create Users

        // Super Admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@oe.hu'],
            [
                'name' => 'Admin Aladár',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::STANDARD,
                'department_id' => $itDept->id,
                'work_schedule_id' => $standardSchedule->id,
                'hired_at' => now()->subYears(2),
                'is_active' => true,
            ]
        );
        $admin->assignRole(RoleType::SUPER_ADMIN->value);

        // HR Manager
        $hrUser = User::firstOrCreate(
            ['email' => 'hr@oe.hu'],
            [
                'name' => 'HR Hédi',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::STANDARD,
                'department_id' => $hrDept->id,
                'work_schedule_id' => $standardSchedule->id,
                'hired_at' => now()->subYears(1),
                'is_active' => true,
            ]
        );
        $hrUser->assignRole(RoleType::HR->value);

        // Manager
        $manager = User::firstOrCreate(
            ['email' => 'manager@oe.hu'],
            [
                'name' => 'Vezető Viktor',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::STANDARD,
                'department_id' => $itDept->id,
                'work_schedule_id' => $standardSchedule->id,
                'hired_at' => now()->subYears(1),
                'is_active' => true,
            ]
        );
        $manager->assignRole(RoleType::MANAGER->value);

        // Employee (Standard)
        $employee = User::firstOrCreate(
            ['email' => 'employee@oe.hu'],
            [
                'name' => 'Dolgozó Dénes',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::STANDARD,
                'department_id' => $itDept->id,
                'manager_id' => $manager->id,
                'work_schedule_id' => $standardSchedule->id,
                'hired_at' => now()->subMonths(6),
                'is_active' => true,
            ]
        );
        $employee->assignRole(RoleType::EMPLOYEE->value);

        // Hourly User (Megbízás / órabér)
        $hourlyUser = User::firstOrCreate(
            ['email' => 'hourly@oe.hu'],
            [
                'name' => 'Órabéres Olga',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::HOURLY,
                'department_id' => $salesDept->id,
                'manager_id' => $manager->id,
                'hired_at' => now()->subMonths(3),
                'is_active' => true,
            ]
        );
        $hourlyUser->assignRole(RoleType::EMPLOYEE->value);

        // Fixed User (Megbízás / fix)
        $fixedUser = User::firstOrCreate(
            ['email' => 'fixed@oe.hu'],
            [
                'name' => 'Fix Ferenc',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::FIXED,
                'department_id' => $salesDept->id,
                'manager_id' => $manager->id,
                'hired_at' => now()->subMonths(3),
                'is_active' => true,
            ]
        );
        $fixedUser->assignRole(RoleType::EMPLOYEE->value);

        // Student User (Diák)
        $studentUser = User::firstOrCreate(
            ['email' => 'student@oe.hu'],
            [
                'name' => 'Diák Dávid',
                'password' => Hash::make('password'),
                'employment_type' => EmploymentType::STUDENT,
                'department_id' => $itDept->id,
                'manager_id' => $manager->id,
                'work_schedule_id' => $studentSchedule ? $studentSchedule->id : null,
                'hired_at' => now()->subMonths(1),
                'is_active' => true,
            ]
        );
        $studentUser->assignRole(RoleType::EMPLOYEE->value);
    }
}
