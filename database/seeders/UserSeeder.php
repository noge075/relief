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
        $itDept = Department::where('name', 'IT')->first();
        $hrDept = Department::where('name', 'HR')->first();
        
        $standardSchedule = WorkSchedule::where('name', 'Standard (40h)')->first();

        // 1. Super Admin
        $admin = User::create([
            'last_name' => 'Super',
            'first_name' => 'Admin',
            'email' => 'admin@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
            'department_id' => $itDept?->id,
            'work_schedule_id' => $standardSchedule?->id,
            'hired_at' => now()->subYears(5),
        ]);
        $admin->assignRole(RoleType::SUPER_ADMIN->value);

        // 2. HR User
        $hr = User::create([
            'last_name' => 'HR',
            'first_name' => 'User',
            'email' => 'hr@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
            'department_id' => $hrDept?->id,
            'work_schedule_id' => $standardSchedule?->id,
            'hired_at' => now()->subYears(3),
        ]);
        $hr->assignRole(RoleType::HR->value);

        // 3. Manager
        $manager = User::create([
            'last_name' => 'Manager',
            'first_name' => 'User',
            'email' => 'manager@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
            'department_id' => $itDept?->id,
            'work_schedule_id' => $standardSchedule?->id,
            'hired_at' => now()->subYears(4),
        ]);
        $manager->assignRole(RoleType::MANAGER->value);

        // 4. Employee (Manager beosztottja)
        $employee = User::create([
            'last_name' => 'Employee',
            'first_name' => 'User',
            'email' => 'employee@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
            'department_id' => $itDept?->id,
            'manager_id' => $manager->id,
            'work_schedule_id' => $standardSchedule?->id,
            'hired_at' => now()->subYears(1),
        ]);
        $employee->assignRole(RoleType::EMPLOYEE->value);
        
        // 5. Payroll User
        $payroll = User::create([
            'last_name' => 'Payroll',
            'first_name' => 'User',
            'email' => 'payroll@oe.hu',
            'password' => Hash::make('password'),
            'employment_type' => EmploymentType::STANDARD,
            'department_id' => $hrDept?->id,
            'work_schedule_id' => $standardSchedule?->id,
            'hired_at' => now()->subYears(2),
        ]);
        $payroll->assignRole(RoleType::PAYROLL->value);

        // További teszt userek
        User::factory(10)->create()->each(function ($user) {
            $user->assignRole(RoleType::EMPLOYEE->value);
        });
    }
}
