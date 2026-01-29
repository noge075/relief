<?php

namespace Database\Seeders;

use App\Models\MonthlyClosure;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MonthlyClosureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser =  User::query()->where('email', '=', 'admin@oe.hu')->first();
        $year = 2025;

        foreach (range(1, 12) as $month) {
            $date = Carbon::create($year, $month, 1);

            MonthlyClosure::updateOrCreate(
                ['month' => $date->toDateString()],
                [
                    'is_closed' => true,
                    'closed_by' => $adminUser->id,
                    'closed_at' => $date->copy()->endOfMonth()->endOfDay(),
                ]
            );
        }
    }
}
