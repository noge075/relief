<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'IT Fejlesztés'],
            ['name' => 'HR & Payroll'],
            ['name' => 'Értékesítés'],
            ['name' => 'Vezetőség'],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
