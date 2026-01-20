<?php

namespace App\Services;

use App\Enums\EmploymentType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\AttendanceLog;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AttendanceService
{
    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected HolidayService $holidayService
    ) {}

    public function getAttendanceData(User $user, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // 1. Tényleges logok
        $logs = AttendanceLog::where('user_id', $user->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        // 2. Szabadságok
        $leaveRequests = $this->leaveRequestRepository->getForUserInPeriod($user->id, $start->format('Y-m-d'), $end->format('Y-m-d'))
            ->where('status', LeaveStatus::APPROVED->value);

        // 3. Ünnepnapok
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        // 4. Munkarend
        $workSchedule = $user->workSchedule;
        $weeklyPattern = $workSchedule ? $workSchedule->weekly_pattern : null;

        // 5. Napok generálása
        $days = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $log = $logs->get($dateStr);
            
            // Alapértelmezett adatok
            $checkIn = null;
            $checkOut = null;
            $workedHours = null;
            $status = null;
            $statusType = null;
            
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);

            // Munkarend ellenőrzése
            $isScheduledWorkday = false;
            if ($weeklyPattern) {
                $dayName = strtolower($date->format('l')); // monday, tuesday...
                if (isset($weeklyPattern[$dayName]) && $weeklyPattern[$dayName] > 0) {
                    $isScheduledWorkday = true;
                }
            } else {
                // Ha nincs munkarend, akkor H-P munkanap
                $isScheduledWorkday = !$isWeekend;
            }
            
            // Ünnepnap felülírja a munkarendet (kivéve extra munkanap)
            if ($isHoliday && !$isExtraWorkday) {
                $isScheduledWorkday = false;
            }
            if ($isExtraWorkday) {
                $isScheduledWorkday = true;
            }

            // Ha van tényleges log, az felülír mindent
            if ($log) {
                $checkIn = $log->check_in ? $log->check_in->format('H:i') : '-';
                $checkOut = $log->check_out ? $log->check_out->format('H:i') : '-';
                $workedHours = $log->worked_hours;
                $status = __($log->status);
                $statusType = $log->status;
            } else {
                // Ha nincs log, akkor számolunk
                
                // Szabadság ellenőrzés
                $leave = null;
                foreach ($leaveRequests as $req) {
                    if ($date->between($req->start_date, $req->end_date)) {
                        $leave = $req;
                        break;
                    }
                }

                if ($leave) {
                    $status = ucfirst($leave->type->value);
                    $statusType = $leave->type->value;
                    
                    if ($leave->type === LeaveType::HOME_OFFICE) {
                        $workedHours = 8; // Vagy a munkarend szerinti óra
                        $checkIn = '09:00';
                        $checkOut = '17:00';
                    }
                } elseif (!$isScheduledWorkday) {
                    $status = $isHoliday ? $holidays[$dateStr]['name'] : __('Weekend');
                    $statusType = $isHoliday ? 'holiday' : 'weekend';
                    
                    // Ha diák és nincs beosztva, akkor csak üres (vagy -)
                    if ($user->employment_type === EmploymentType::STUDENT && !$isHoliday) {
                        $status = '-';
                        $statusType = 'none';
                    }
                } else {
                    // Munkanap (Scheduled)
                    if ($user->employment_type === EmploymentType::STANDARD) {
                        $checkIn = '09:00';
                        $checkOut = '17:00';
                        $workedHours = $weeklyPattern ? $weeklyPattern[strtolower($date->format('l'))] : 8;
                        $status = __('Present');
                        $statusType = 'present';
                    } else {
                        // Órabéres/Diák: ha nincs log, de be volt osztva -> Hiányzás? Vagy csak üres.
                        $status = __('Scheduled');
                        $statusType = 'scheduled';
                    }
                }
            }

            $days[] = [
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'worked_hours' => $workedHours,
                'status' => $status,
                'status_type' => $statusType,
                'is_weekend' => $isWeekend,
                'is_today' => $date->isToday(),
                'is_holiday' => $isHoliday,
            ];
        }
        
        return $days;
    }
}
