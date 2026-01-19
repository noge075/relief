<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->endOfMonth(); // Mai napig vagy hónap végéig
        $period = CarbonPeriod::create($start, $end);

        $users = User::all();

        foreach ($users as $user) {
            // Ha admin vagy HR, nekik is generálhatunk, de fókuszáljunk a teszt userekre
            if (in_array($user->email, ['admin@oe.hu', 'hr@oe.hu'])) {
                continue;
            }

            foreach ($period as $date) {
                if ($date->isFuture()) continue;

                $isWeekend = $date->isWeekend();
                
                // 1. Standard & Fixed: Hétköznap dolgozik, kivéve ha szabi
                if (in_array($user->email, ['employee@oe.hu', 'fixed@oe.hu', 'manager@oe.hu'])) {
                    if (!$isWeekend) {
                        // 10% esély szabadságra/betegségre
                        if (rand(1, 100) <= 5) {
                            $this->createLeave($user, $date, LeaveType::VACATION);
                        } elseif (rand(1, 100) <= 2) {
                            $this->createLeave($user, $date, LeaveType::SICK);
                        } else {
                            // Jelenlét
                            // Standardnál nem kötelező a log, de a riport miatt jó ha van, 
                            // vagy a riportnak kell tudnia generálni.
                            // A jelenlegi PayrollService a logokat nem nézi a "Worked Days" számításnál,
                            // hanem (Munkanap - Távollét).
                            // De a MyAttendance nézetben a logokat mutatja.
                            // Generáljunk logot, hogy látszódjon.
                            $this->createLog($user, $date, '09:00', '17:00');
                        }
                    }
                }

                // 2. Hourly: Változó
                if ($user->email === 'hourly@oe.hu') {
                    // Csak Hétfő, Szerda, Péntek
                    if (in_array($date->dayOfWeek, [1, 3, 5])) {
                        $startHour = rand(8, 10);
                        $duration = rand(4, 8);
                        $this->createLog($user, $date, sprintf('%02d:00', $startHour), sprintf('%02d:00', $startHour + $duration));
                    }
                }

                // 3. Student: Ritkábban
                if ($user->email === 'student@oe.hu') {
                    // Csak Kedd, Csütörtök
                    if (in_array($date->dayOfWeek, [2, 4])) {
                        $this->createLog($user, $date, '14:00', '18:00');
                    }
                }
            }
        }
    }

    private function createLog($user, $date, $startTime, $endTime)
    {
        // Ellenőrizzük, nincs-e már (bár a seeder futhat üres táblára)
        if (AttendanceLog::where('user_id', $user->id)->where('date', $date->format('Y-m-d'))->exists()) {
            return;
        }

        $checkIn = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
        $checkOut = Carbon::parse($date->format('Y-m-d') . ' ' . $endTime);
        $hours = $checkIn->diffInHours($checkOut);

        AttendanceLog::create([
            'user_id' => $user->id,
            'date' => $date,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'worked_hours' => $hours,
            'status' => 'present',
        ]);
    }

    private function createLeave($user, $date, LeaveType $type)
    {
        // Ellenőrizzük, nincs-e már
        // Egyszerűsítve: csak 1 napos
        
        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'start_date' => $date,
            'end_date' => $date,
            'days_count' => 1,
            'reason' => 'Seeded ' . $type->value,
            'status' => LeaveStatus::APPROVED,
            'approver_id' => 1, // Admin
        ]);
    }
}
