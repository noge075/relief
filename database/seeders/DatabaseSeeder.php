<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            HomeOfficePolicySeeder::class,
            UserSeeder::class,
            LeaveBalanceSeeder::class,
            AttendanceSeeder::class,
            MonthlyClosureSeeder::class,
        ]);
    }
}