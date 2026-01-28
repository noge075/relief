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
use Illuminate\Support\Collection;

class AttendanceService
{
    protected const string DEFAULT_START_TIME = '09:00';
    protected const string DEFAULT_END_TIME = '17:00';
    protected const int DEFAULT_WORK_HOURS = 8;

    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected HolidayService $holidayService
    ) {}

    /**
     * Build the daily attendance report for a given month.
     */
    public function getAttendanceData(User $user, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // 1. Pre-fetch all necessary data to avoid N+1 queries in loop
        $context = [
            'logs' => $this->getLogsKeyedByDate($user->id, $start, $end),
            'leaves' => $this->getApprovedLeaves($user->id, $start, $end),
            'holidays' => $this->holidayService->getHolidaysInRange($start, $end),
            'extra_workdays' => $this->holidayService->getExtraWorkdaysInRange($start, $end),
            'schedule' => $user->workSchedule?->weekly_pattern,
        ];

        // 2. Generate days
        $days = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($period as $date) {
            $days[] = $this->processDay($date, $user, $context);
        }

        return $days;
    }

    /**
     * Process a single day logic.
     */
    protected function processDay(Carbon $date, User $user, array $context): array
    {
        $dateStr = $date->format('Y-m-d');

        // Base structure
        $dayData = [
            'date' => $date,
            'is_weekend' => $date->isWeekend(),
            'is_today' => $date->isToday(),
            'is_holiday' => isset($context['holidays'][$dateStr]),
            'check_in' => null,
            'check_out' => null,
            'worked_hours' => null,
            'status' => null,
            'status_type' => null,
        ];

        // Determine if it should be a workday based on schedule/holidays
        $isScheduled = $this->isScheduledWorkday(
            $date,
            $context['schedule'],
            $dayData['is_holiday'],
            isset($context['extra_workdays'][$dateStr])
        );

        // Priority 1: Actual Attendance Log exists
        if ($log = $context['logs']->get($dateStr)) {
            return $this->applyLogData($dayData, $log);
        }

        // Priority 2: Approved Leave exists
        if ($leave = $this->findLeaveForDate($date, $context['leaves'])) {
            return $this->applyLeaveData($dayData, $leave, $context['schedule']);
        }

        // Priority 3: No Log, No Leave (Default Schedule Logic)
        return $this->applyScheduleData($dayData, $user, $isScheduled, $context['holidays'], $context['schedule']);
    }

    /**
     * Logic to determine if a specific date is a working day.
     */
    protected function isScheduledWorkday(Carbon $date, ?array $weeklyPattern, bool $isHoliday, bool $isExtraWorkday): bool
    {
        if ($isExtraWorkday) {
            return true;
        }

        if ($isHoliday) {
            return false;
        }

        // If no custom pattern, fallback to standard M-F
        if (empty($weeklyPattern)) {
            return !$date->isWeekend();
        }

        $dayName = strtolower($date->format('l'));
        return isset($weeklyPattern[$dayName]) && $weeklyPattern[$dayName] > 0;
    }

    protected function applyLogData(array $data, AttendanceLog $log): array
    {
        $data['check_in'] = $log->check_in?->format('H:i') ?? '-';
        $data['check_out'] = $log->check_out?->format('H:i') ?? '-';
        $data['worked_hours'] = $log->worked_hours;
        $data['status'] = __($log->status);
        $data['status_type'] = $log->status;

        return $data;
    }

    protected function applyLeaveData(array $data, $leave, ?array $weeklyPattern): array
    {
        $data['status'] = ucfirst($leave->type->value);
        $data['status_type'] = $leave->type->value;

        // Home Office is treated as a worked day in terms of hours
        if ($leave->type === LeaveType::HOME_OFFICE) {
            $dayName = strtolower($data['date']->format('l'));

            $data['worked_hours'] = $weeklyPattern[$dayName] ?? self::DEFAULT_WORK_HOURS;
            $data['check_in'] = self::DEFAULT_START_TIME;
            $data['check_out'] = self::DEFAULT_END_TIME;
        }

        return $data;
    }

    protected function applyScheduleData(array $data, User $user, bool $isScheduled, array $holidays, ?array $weeklyPattern): array
    {
        $dateStr = $data['date']->format('Y-m-d');

        if (!$isScheduled) {
            // It's a day off (Weekend or Holiday)
            $data['status'] = $data['is_holiday']
                ? ($holidays[$dateStr]['name'] ?? __('Holiday'))
                : __('Weekend');
            $data['status_type'] = $data['is_holiday'] ? 'holiday' : 'weekend';

            // Exception: Students/Hourly workers show empty status on off days if not a holiday
            if ($user->employment_type === EmploymentType::STUDENT && !$data['is_holiday']) {
                $data['status'] = '-';
                $data['status_type'] = 'none';
            }

            return $data;
        }

        // It is a scheduled workday, but no log exists
        if ($user->employment_type === EmploymentType::STANDARD) {
            // Standard employees get auto-filled "Present"
            $dayName = strtolower($data['date']->format('l'));

            $data['check_in'] = self::DEFAULT_START_TIME;
            $data['check_out'] = self::DEFAULT_END_TIME;
            $data['worked_hours'] = $weeklyPattern[$dayName] ?? self::DEFAULT_WORK_HOURS;
            $data['status'] = __('Present');
            $data['status_type'] = 'present';
        } else {
            // Students/Hourly: Shows as Scheduled (or Absent) but no hours
            $data['status'] = __('Scheduled');
            $data['status_type'] = 'scheduled';
        }

        return $data;
    }

    protected function getLogsKeyedByDate(int $userId, Carbon $start, Carbon $end): Collection
    {
        return AttendanceLog::where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->keyBy(fn ($item) => $item->date->format('Y-m-d'));
    }

    protected function getApprovedLeaves(int $userId, Carbon $start, Carbon $end): Collection
    {
        return $this->leaveRequestRepository
            ->getForUserInPeriod($userId, $start->format('Y-m-d'), $end->format('Y-m-d'))
            ->where('status', LeaveStatus::APPROVED->value);
    }

    protected function findLeaveForDate(Carbon $date, Collection $leaves)
    {
        // Since leaves collection is small, iteration is acceptable.
        // For optimization with large datasets, consider an Interval Tree or separating days in query.
        return $leaves->first(function ($req) use ($date) {
            return $date->between($req->start_date, $req->end_date);
        });
    }
}