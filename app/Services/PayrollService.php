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
use Carbon\CarbonImmutable;
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
        [$start, $end] = $this->getMonthBounds($year, $month);
        [$users, $leaveRequests] = $this->getReportData($start, $end);

        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        $totalWorkdays = $this->calculateWorkingDaysInPeriod($start, $end, $holidays, $extraWorkdays);

        $report = collect();

        foreach ($users as $user) {
            $stats = [
                'vacation' => 0,
                'sick' => 0,
                'home_office' => 0,
            ];

            if (isset($leaveRequests[$user->id])) {
                foreach ($leaveRequests[$user->id] as $request) {
                    $overlapStart = $request->start_date->max($start);
                    $overlapEnd = $request->end_date->min($end);

                    if ($overlapStart <= $overlapEnd) {
                        $days = $this->calculateWorkingDaysInPeriod($overlapStart, $overlapEnd, $holidays, $extraWorkdays);

                        match ($request->type) {
                            LeaveType::VACATION => $stats['vacation'] += $days,
                            LeaveType::SICK => $stats['sick'] += $days,
                            LeaveType::HOME_OFFICE => $stats['home_office'] += $days,
                            default => null,
                        };
                    }
                }
            }

            $workedDays = $totalWorkdays - $stats['vacation'] - $stats['sick'];

            $report->push([
                'user_id' => $user->id,
                'employee_id' => "{$user->name} (ID: {$user->id})",
                'name' => $user->name,
                'department' => $user->departments->pluck('name')->implode(', '),
                'group' => $user->workSchedule->name ?? '-',
                'month' => $start->format('Y-m'),
                'total_workdays' => $totalWorkdays,
                'worked_days' => $workedDays,
                'vacation_days' => $stats['vacation'],
                'sick_days' => $stats['sick'],
                'home_office_days' => $stats['home_office'],
            ]);
        }

        return $report;
    }

    public function getDailyReportData(int $year, int $month): Collection
    {
        [$start, $end] = $this->getMonthBounds($year, $month);
        [$users, $leaveRequests] = $this->getReportData($start, $end);
        $dayMap = $this->generateDayMap($start, $end);

        $report = collect();

        foreach ($dayMap as $dateStr => $dayInfo) {
            $currentDate = Carbon::parse($dateStr);

            foreach ($users as $user) {
                $status = $dayInfo['is_workday'] ? 'Present' : 'Off';
                $meta = $dayInfo['meta'];

                if (isset($leaveRequests[$user->id])) {
                    foreach ($leaveRequests[$user->id] as $request) {
                        if ($currentDate->between($request->start_date, $request->end_date)) {
                            $status = ucfirst($request->type->value);
                            $meta = $request->reason; // Assuming reason exists on model
                            break;
                        }
                    }
                }

                $report->push([
                    'date' => $dateStr,
                    'name' => $user->name,
                    'employee_id' => "{$user->name} (ID: {$user->id})",
                    'department' => $user->departments->pluck('name')->implode(', '),
                    'group' => $user->workSchedule->name ?? '-',
                    'status' => $status,
                    'meta' => $meta,
                ]);
            }
        }

        return $report;
    }

    public function isMonthClosed(int $year, int $month): bool
    {
        return MonthlyClosure::where('month', $this->getMonthStartDateString($year, $month))
            ->where('is_closed', true)
            ->exists();
    }

    public function closeMonth(int $year, int $month, User $user): void
    {
        $this->updateMonthlyClosureStatus($year, $month, $user, true);
    }

    public function reopenMonth(int $year, int $month, User $user): void
    {
        $this->updateMonthlyClosureStatus($year, $month, $user, false);
    }

    public function getClosureStatus(int $year, int $month): ?MonthlyClosure
    {
        return MonthlyClosure::where('month', $this->getMonthStartDateString($year, $month))->first();
    }

    public function storeExport(int $year, int $month, User $user, $fileContent, string $filename): void
    {
        $dateStr = $this->getMonthStartDateString($year, $month);

        $closure = MonthlyClosure::firstOrCreate(
            ['month' => $dateStr],
            ['is_closed' => false]
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'payroll_export');
        file_put_contents($tempPath, $fileContent);

        $closure->addMedia($tempPath)
            ->usingFileName($filename)
            ->toMediaCollection('exports');
    }

    public function getExports(int $year, int $month, int $perPage = 5, int $page = 1): LengthAwarePaginator
    {
        $closure = $this->getClosureStatus($year, $month);

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

    protected function getReportData(Carbon $start, Carbon $end): array
    {
        $users = $this->getActiveUsers();
        $leaveRequests = $this->getApprovedLeaveRequests($users->pluck('id'), $start, $end);
        return [$users, $leaveRequests];
    }

    protected function getActiveUsers(): Collection
    {
        return User::with(['departments', 'workSchedule'])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    protected function getApprovedLeaveRequests(Collection $userIds, Carbon $start, Carbon $end): Collection
    {
        return LeaveRequest::whereIn('user_id', $userIds)
            ->where('status', LeaveStatus::APPROVED->value)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                    ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get()
            ->groupBy('user_id');
    }

    protected function generateDayMap(Carbon $start, Carbon $end): array
    {
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);
        $period = CarbonPeriod::create($start, $end);

        $map = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isWeekend();
            $isHoliday = isset($holidays[$dateStr]);
            $isExtraWorkday = isset($extraWorkdays[$dateStr]);

            $isWorkday = true;
            $meta = null;

            if (($isWeekend || $isHoliday) && !$isExtraWorkday) {
                $isWorkday = false;
                $meta = $isHoliday ? ($holidays[$dateStr]['name'] ?? 'Holiday') : 'Weekend';
            } elseif ($isExtraWorkday) {
                $meta = $extraWorkdays[$dateStr]['name'] ?? 'Extra Workday';
            }

            $map[$dateStr] = [
                'is_workday' => $isWorkday,
                'meta' => $meta
            ];
        }

        return $map;
    }

    protected function calculateWorkingDaysInPeriod(
        Carbon|CarbonImmutable $start,
        Carbon|CarbonImmutable $end,
        array $holidays,
        array $extraWorkdays
    ): int {
        $days = 0;
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isWeekend();

            if (isset($extraWorkdays[$dateStr])) {
                $days++;
                continue;
            }

            if ($isWeekend || isset($holidays[$dateStr])) {
                continue;
            }

            $days++;
        }

        return $days;
    }

    protected function updateMonthlyClosureStatus(int $year, int $month, User $user, bool $isClosed): void
    {
        $dateStr = $this->getMonthStartDateString($year, $month);

        MonthlyClosure::updateOrCreate(
            ['month' => $dateStr],
            [
                'is_closed' => $isClosed,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]
        );

        $notification = $isClosed
            ? new MonthlyClosureNotification($year, $month, $user)
            : new MonthReopenedNotification($year, $month, $user);

        Notification::send(User::role(RoleType::PAYROLL->value)->get(), $notification);
    }

    protected function getMonthBounds(int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        return [$start, $end];
    }

    protected function getMonthStartDateString(int $year, int $month): string
    {
        return Carbon::createFromDate($year, $month, 1)->format('Y-m-d');
    }
}
