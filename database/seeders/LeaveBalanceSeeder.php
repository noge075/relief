<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\LeaveType;
use App\Models\LeaveBalance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $year = Carbon::now()->year;
        $users = User::where('employment_type', EmploymentType::STANDARD)->get();

        foreach ($users as $user) {
            // Ellenőrizzük, van-e már kerete
            if (LeaveBalance::where('user_id', $user->id)->where('year', $year)->where('type', LeaveType::VACATION->value)->exists()) {
                continue;
            }

            // Alapkeret + életkor alapú pótszabadság (szimulálva)
            // Alap: 20 nap.
            // +1 nap minden 3 év után (csak példa)
            $age = rand(25, 50); // Szimulált életkor
            $extra = floor(($age - 25) / 3);
            $allowance = 20 + max(0, $extra);
            
            // Maximum 30 nap
            $allowance = min(30, $allowance);

            LeaveBalance::create([
                'user_id' => $user->id,
                'year' => $year,
                'type' => LeaveType::VACATION->value,
                'allowance' => $allowance,
                'used' => 0, // Kezdetben 0, majd a LeaveRequest seeder növeli, vagy újraszámoljuk
            ]);
        }
    }
}
