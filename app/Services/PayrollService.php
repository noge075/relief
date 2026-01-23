<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\RoleType;
use App\Models\LeaveRequest;
use App\Models\MonthlyClosure;
use App\Models\User;
use App\Notifications\MonthlyClosureNotification;
use App\Notifications\MonthReopenedNotification;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

class PayrollService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected HolidayService $holidayService
    ) {}

    public function getMonthlyReportData(int $year, int $month): Collection
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // 1. Dolgozók
        $users = User::with(['department', 'workSchedule'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        // 2. Munkanapok száma a hónapban
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);
        
        $totalWorkdays = 0;
        $period = CarbonPeriod::create($start, $end);
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);

            if (($isWeekend || $isHoliday) && !$isExtraWorkday) {
                continue;
            }
            $totalWorkdays++;
        }

        // 3. Távollétek
        $leaveRequests = LeaveRequest::whereIn('user_id', $users->pluck('id'))
            ->where('status', LeaveStatus::APPROVED->value)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get()
            ->groupBy('user_id');

        $report = collect();

        foreach ($users as $user) {
            $vacationDays = 0;
            $sickDays = 0;
            $homeOfficeDays = 0;

            if (isset($leaveRequests[$user->id])) {
                foreach ($leaveRequests[$user->id] as $request) {
                    $reqStart = Carbon::parse($request->start_date);
                    $reqEnd = Carbon::parse($request->end_date);
                    
                    $overlapStart = $reqStart->max($start);
                    $overlapEnd = $reqEnd->min($end);
                    
                    if ($overlapStart <= $overlapEnd) {
                        $days = $this->calculateWorkingDaysInPeriod($overlapStart, $overlapEnd, $holidays, $extraWorkdays);
                        
                        if ($request->type === LeaveType::VACATION) {
                            $vacationDays += $days;
                        } elseif ($request->type === LeaveType::SICK) {
                            $sickDays += $days;
                        } elseif ($request->type === LeaveType::HOME_OFFICE) {
                            $homeOfficeDays += $days;
                        }
                    }
                }
            }

            $workedDays = $totalWorkdays - $vacationDays - $sickDays;

            $report->push([
                'user_id' => $user->id,
                'employee_id' => $user->name . ' (ID: ' . $user->id . ')',
                'name' => $user->name,
                'department' => $user->department->name ?? '-',
                'group' => $user->workSchedule->name ?? '-',
                'month' => $start->format('Y-m'),
                'total_workdays' => $totalWorkdays,
                'worked_days' => $workedDays,
                'vacation_days' => $vacationDays,
                'sick_days' => $sickDays,
                'home_office_days' => $homeOfficeDays,
            ]);
        }

        return $report;
    }

    public function getDailyReportData(int $year, int $month): Collection
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $users = User::with(['department', 'workSchedule'])->where('is_active', true)->orderBy('name')->get();
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        $leaveRequests = LeaveRequest::whereIn('user_id', $users->pluck('id'))
            ->where('status', LeaveStatus::APPROVED->value)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get()
            ->groupBy('user_id');

        $report = collect();
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            
            // Nap típusa
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);
            
            $dayType = 'workday';
            $dayMeta = null;

            if (($isWeekend || $isHoliday) && !$isExtraWorkday) {
                $dayType = 'off';
                $dayMeta = $isHoliday ? $holidays[$dateStr]['name'] : 'Weekend';
            } elseif ($isExtraWorkday) {
                $dayMeta = $extraWorkdays[$dateStr]['name'];
            }

            foreach ($users as $user) {
                $status = $dayType === 'workday' ? 'Present' : 'Off';
                $meta = $dayMeta;

                if (isset($leaveRequests[$user->id])) {
                    foreach ($leaveRequests[$user->id] as $request) {
                        if ($date->between($request->start_date, $request->end_date)) {
                            $status = ucfirst($request->type->value);
                            $meta = $request->reason;
                            break;
                        }
                    }
                }

                $report->push([
                    'date' => $dateStr,
                    'name' => $user->name,
                    'employee_id' => $user->name . ' (ID: ' . $user->id . ')',
                    'department' => $user->department->name ?? '-',
                    'group' => $user->workSchedule->name ?? '-',
                    'status' => $status,
                    'meta' => $meta,
                ]);
            }
        }

        return $report;
    }

    protected function calculateWorkingDaysInPeriod(Carbon $start, Carbon $end, array $holidays, array $extraWorkdays): int
    {
        $days = 0;
        $period = CarbonPeriod::create($start, $end);
        
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);

            if (($isWeekend || $isHoliday) && !$isExtraWorkday) {
                continue;
            }
            $days++;
        }
        
        return $days;
    }

    public function isMonthClosed(int $year, int $month): bool
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        return MonthlyClosure::where('month', $date)->where('is_closed', true)->exists();
    }

    public function closeMonth(int $year, int $month, User $user): void
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        
        MonthlyClosure::updateOrCreate(
            ['month' => $date],
            [
                'is_closed' => true,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]
        );
        
        // Értesítés a Payrollosoknak
        $payrollUsers = User::role(RoleType::PAYROLL->value)->get();
        Notification::send($payrollUsers, new MonthlyClosureNotification($year, $month, $user));
    }

    public function reopenMonth(int $year, int $month, User $user): void
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        
        MonthlyClosure::updateOrCreate(
            ['month' => $date],
            [
                'is_closed' => false,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]
        );
        
        // Értesítés a Payrollosoknak
        $payrollUsers = User::role(RoleType::PAYROLL->value)->get();
        Notification::send($payrollUsers, new MonthReopenedNotification($year, $month, $user));
    }
    
    public function getClosureStatus(int $year, int $month): ?MonthlyClosure
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        return MonthlyClosure::where('month', $date)->first();
    }
    
    // --- Exports ---
    
    public function storeExport(int $year, int $month, User $user, $fileContent, string $filename): void
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        
        $closure = MonthlyClosure::firstOrCreate(
            ['month' => $date],
            ['is_closed' => false]
        );
        
        $tempPath = tempnam(sys_get_temp_dir(), 'payroll_export');
        file_put_contents($tempPath, $fileContent);
        
        $closure->addMedia($tempPath)
               ->usingFileName($filename)
               ->toMediaCollection('exports');
    }
    
    public function getExports(int $year, int $month, int $perPage = 5, int $page = 1)
    {
        $date = Carbon::createFromDate($year, $month, 1)->startOfMonth()->format('Y-m-d');
        $closure = MonthlyClosure::where('month', $date)->first();
        
        if (!$closure) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }
        
        $media = $closure->getMedia('exports')->sortByDesc('created_at');
        
        $items = $media->slice(($page - 1) * $perPage, $perPage)->values();
        
        return new LengthAwarePaginator(
            $items,
            $media->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );
    }
}
