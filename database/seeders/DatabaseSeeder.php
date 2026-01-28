<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            DepartmentSeeder::class,
            WorkScheduleSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            LeaveBalanceSeeder::class,
            AttendanceSeeder::class,
        ]);
    }
}
