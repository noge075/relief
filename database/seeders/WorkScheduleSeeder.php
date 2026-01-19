<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Standard Hétfő-Péntek
        WorkSchedule::firstOrCreate(
            ['name' => 'Standard H-P 8ó'],
            [
                'weekly_pattern' => [
                    'monday' => 8, 'tuesday' => 8, 'wednesday' => 8, 'thursday' => 8, 'friday' => 8, 'saturday' => 0, 'sunday' => 0
                ]
            ]
        );

        // 2. Diák Kedd-Csütörtök
        WorkSchedule::firstOrCreate(
            ['name' => 'Diák Kedd-Csütörtök'],
            [
                'weekly_pattern' => [
                    'monday' => 0, 'tuesday' => 4, 'wednesday' => 0, 'thursday' => 4, 'friday' => 0, 'saturday' => 0, 'sunday' => 0
                ]
            ]
        );

        // 3. Részmunkaidős (4 órás)
         WorkSchedule::firstOrCreate(
             ['name' => 'Részmunkaidő H-P 4ó'],
             [
                 'weekly_pattern' => [
                     'monday' => 4, 'tuesday' => 4, 'wednesday' => 4, 'thursday' => 4, 'friday' => 4, 'saturday' => 0, 'sunday' => 0
                 ]
             ]
         );
    }
}
