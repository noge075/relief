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
        WorkSchedule::create([
            'name' => 'Standard H-P 8ó',
            'weekly_pattern' => [
                'mon' => 8, 'tue' => 8, 'wed' => 8, 'thu' => 8, 'fri' => 8, 'sat' => 0, 'sun' => 0
            ]
        ]);

        // 2. Diák Kedd-Csütörtök
        WorkSchedule::create([
            'name' => 'Diák Kedd-Csütörtök',
            'weekly_pattern' => [
                'mon' => 0, 'tue' => 8, 'wed' => 0, 'thu' => 8, 'fri' => 0, 'sat' => 0, 'sun' => 0
            ]
        ]);

        // 3. Részmunkaidős (4 órás)
         WorkSchedule::create([
             'name' => 'Részmunkaidő H-P 4ó',
             'weekly_pattern' => [
                 'mon' => 4, 'tue' => 4, 'wed' => 4, 'thu' => 4, 'fri' => 4, 'sat' => 0, 'sun' => 0
             ]
         ]);
    }
}
