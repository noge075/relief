<?php

namespace App\Services;

use App\Enums\AttendanceStatusType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\User;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use Carbon\Carbon;

class AttendanceService
{
    public function __construct(
        protected HolidayService $holidayService,
        protected AttendanceLogRepositoryInterface $attendanceLogRepository
    ) {}

    public function generateLogForUser(User $user, Carbon $date): void
    {
        // 1. Is it a designated holiday?
        if ($this->holidayService->isHoliday($date)) {
            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::HOLIDAY
            );
            return;
        }

        // 2. Is it a scheduled workday for the user?
        $workSchedule = $user->workSchedule;
        if ($workSchedule && $workSchedule->isWorkday($date)) {
            // 2a. Check for an approved leave request on this workday
            $leaveRequest = $user->leaveRequests()
                ->where('status', LeaveStatus::APPROVED)
                ->where('start_date', '<=', $date)
                ->where('end_date', '>=', $date)
                ->first();

            if ($leaveRequest) {
                $attendanceStatus = match ($leaveRequest->type) {
                    LeaveType::VACATION => AttendanceStatusType::VACATION,
                    LeaveType::SICK => AttendanceStatusType::SICK_LEAVE,
                    LeaveType::HOME_OFFICE => AttendanceStatusType::HOME_OFFICE,
                    LeaveType::UNPAID => AttendanceStatusType::UNPAID,
                    default => AttendanceStatusType::OFF,
                };
                $this->attendanceLogRepository->updateOrCreateLog($user->id, $date->toDateString(), $attendanceStatus);
                return;
            }

            // 2b. If it's a workday with no leave, mark as present
            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::PRESENT,
                $workSchedule->getWorkHoursForDay($date)
            );
            return;
        }
        
        // 3. Is it a weekend?
        if ($date->isWeekend()) {
            $this->attendanceLogRepository->updateOrCreateLog(
                $user->id,
                $date->toDateString(),
                AttendanceStatusType::WEEKEND
            );
            return;
        }

        // 4. If it's not a holiday, not a workday, and not a weekend, it's a scheduled day OFF
        $this->attendanceLogRepository->updateOrCreateLog(
            $user->id,
            $date->toDateString(),
            AttendanceStatusType::OFF
        );
    }
}
