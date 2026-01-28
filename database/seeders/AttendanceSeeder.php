<?php

namespace Database\Seeders;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AttendanceLog;
use App\Models\LeaveBalance;
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
        // 1. Múltbeli adatok (Jelenlét + Szabadság)
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->endOfMonth(); 
        $period = CarbonPeriod::create($start, $end);

        $users = User::all();

        foreach ($users as $user) {
            if (in_array($user->email, ['admin@oe.hu', 'hr@oe.hu'])) {
                continue;
            }

            foreach ($period as $date) {
                if ($date->isFuture()) continue;

                $isWeekend = $date->isWeekend();
                
                // 1. Standard & Fixed: Hétköznap dolgozik, kivéve ha szabi
                if (in_array($user->email, ['employee@oe.hu', 'fixed@oe.hu', 'manager@oe.hu'])) {
                    if (!$isWeekend) {
                        // 5% esély szabadságra/betegségre
                        if (rand(1, 100) <= 5) {
                            $this->createLeave($user, $date, LeaveType::VACATION);
                        } elseif (rand(1, 100) <= 2) {
                            $this->createLeave($user, $date, LeaveType::SICK);
                        } else {
                            // Jelenlét
                            $this->createLog($user, $date, '09:00', '17:00');
                        }
                    }
                }

                // 2. Hourly: Változó
                if ($user->email === 'hourly@oe.hu') {
                    if (in_array($date->dayOfWeek, [1, 3, 5])) {
                        $startHour = rand(8, 10);
                        $duration = rand(4, 8);
                        $this->createLog($user, $date, sprintf('%02d:00', $startHour), sprintf('%02d:00', $startHour + $duration));
                    }
                }

                // 3. Student: Ritkábban
                if ($user->email === 'student@oe.hu') {
                    if (in_array($date->dayOfWeek, [2, 4])) {
                        $this->createLog($user, $date, '14:00', '18:00');
                    }
                }
            }
        }

        // 2. Jövőbeli szabadságok (Következő 3 hónap)
        $futureStart = Carbon::now()->addDay();
        $futureEnd = Carbon::now()->addMonths(3);
        
        foreach ($users as $user) {
            // Csak Standard és Fixed usereknek generálunk jövőbeli szabadságot
            if (!in_array($user->email, ['employee@oe.hu', 'fixed@oe.hu', 'manager@oe.hu'])) {
                continue;
            }

            // Generáljunk 1-2 hosszabb szabadságot (pl. 1 hét) és pár szórványos napot
            
            // 1. Hosszú szabadság (5 nap)
            $randomStart = Carbon::createFromTimestamp(rand($futureStart->timestamp, $futureEnd->timestamp));
            if ($randomStart->isWeekend()) $randomStart->next('Monday');
            
            $this->createLeaveRange($user, $randomStart, 5, LeaveType::VACATION);

            // 2. Szórványos napok (3 db)
            for ($i = 0; $i < 3; $i++) {
                $randomDate = Carbon::createFromTimestamp(rand($futureStart->timestamp, $futureEnd->timestamp));
                if ($randomDate->isWeekend()) continue;
                
                // Kerüljük az átfedést (egyszerűsítve: ha már van request arra a napra, skip)
                if (LeaveRequest::where('user_id', $user->id)->whereDate('start_date', '<=', $randomDate)->whereDate('end_date', '>=', $randomDate)->exists()) {
                    continue;
                }

                $type = rand(1, 10) > 8 ? LeaveType::SICK : LeaveType::VACATION; // Ritkán beteg
                $this->createLeave($user, $randomDate, $type);
            }
        }
    }

    private function createLog($user, $date, $startTime, $endTime)
    {
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
        $this->createLeaveRange($user, $date, 1, $type);
    }

    private function createLeaveRange($user, $start, $days, LeaveType $type)
    {
        // Számoljuk ki a vége dátumot (hétvégéket átugorva)
        $end = $start->copy();
        $addedDays = 1;
        while ($addedDays < $days) {
            $end->addDay();
            if (!$end->isWeekend()) {
                $addedDays++;
            }
        }

        // Ellenőrzés
        if (LeaveRequest::where('user_id', $user->id)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            })->exists()) {
            return;
        }

        // Ha Vacation, ellenőrizzük a keretet
        if ($type === LeaveType::VACATION) {
            $balance = LeaveBalance::where('user_id', $user->id)->where('year', $start->year)->where('type', 'vacation')->first();
            if ($balance) {
                if (($balance->allowance - $balance->used) < $days) {
                    return; // Nincs elég keret
                }
                // Növeljük a felhasználtat
                $balance->increment('used', $days);
            } else {
                // Ha nincs keret, de Standard, akkor ne hozzunk létre (vagy hozzunk létre keretet is?)
                // A LeaveBalanceSeeder fut előbb, tehát elvileg van keret.
                // Ha mégsem, akkor skip.
                return;
            }
        }

        LeaveRequest::create([
            'user_id' => $user->id,
            'type' => $type,
            'start_date' => $start,
            'end_date' => $end,
            'days_count' => $days,
            'reason' => 'Seeded ' . $type->value,
            'status' => LeaveStatus::APPROVED,
            'approver_id' => 1,
        ]);
    }
}
