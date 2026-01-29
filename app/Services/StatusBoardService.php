<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PermissionType;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class StatusBoardService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected HolidayService $holidayService
    ) {}

    public function getStatusMatrix(User $actor, Carbon $start, Carbon $end, array $filters = []): array
    {
        $users = User::with('workSchedule', 'departments')
            ->where('is_active', true)
            ->when($filters['department_id'] ?? null, function ($q, $id) {
                $q->whereHas('departments', function ($query) use ($id) {
                    $query->where('departments.id', $id);
                });
            })
            ->when($filters['manager_id'] ?? null, function ($q, $id) {
                $q->where('manager_id', $id);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%' . $search . '%')
                          ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        $leaveRequests = LeaveRequest::whereIn('user_id', $users->pluck('id'))
            ->whereIn('status', [LeaveStatus::APPROVED->value, LeaveStatus::PENDING->value])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get()
            ->groupBy('user_id');

        $matrix = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($users as $user) {
            $canViewDetails = $actor->id === $user->id || 
                              $actor->id === $user->manager_id || 
                              $actor->can(PermissionType::VIEW_LEAVE_REQUEST_DETAILS->value);

            $userRow = [
                'user' => $user,
                'days' => []
            ];
            
            $weeklyPattern = $user->workSchedule ? $user->workSchedule->weekly_pattern : null;

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $status = 'present';
                $meta = null;
                $isPending = false;

                $isWeekend = $date->isWeekend();
                $isHoliday = isset($holidays[$dateStr]);
                $isExtraWorkday = isset($extraWorkdays[$dateStr]);
                
                $isScheduledWorkday = false;

                if ($weeklyPattern) {
                    $dayName = strtolower($date->format('l'));
                    if (isset($weeklyPattern[$dayName]) && $weeklyPattern[$dayName] > 0) {
                        $isScheduledWorkday = true;
                    }
                } else {
                    $isScheduledWorkday = !$isWeekend;
                }

                if ($isHoliday && !$isExtraWorkday) {
                    $isScheduledWorkday = false;
                }

                if ($isExtraWorkday) {
                    $isScheduledWorkday = true;
                }

                if (!$isScheduledWorkday) {
                    $status = 'off';
                    if ($isHoliday) {
                        $meta = $holidays[$dateStr]['name'];
                    } elseif ($isWeekend) {
                        $meta = __('Weekend');
                    } else {
                        $meta = __('Off');
                    }
                } elseif ($isExtraWorkday) {
                    $meta = $extraWorkdays[$dateStr]['name'] ?? __('Extra Workday');
                }

                if (isset($leaveRequests[$user->id])) {
                    foreach ($leaveRequests[$user->id] as $request) {
                        if ($date->between($request->start_date, $request->end_date)) {
                            $status = $request->type->value; // vacation, sick, home_office
                            $meta = $canViewDetails ? ($request->reason ?: null) : null;
                            $isPending = $request->status === LeaveStatus::PENDING;

                            break;
                        }
                    }
                }

                $userRow['days'][$dateStr] = [
                    'status' => $status,
                    'meta' => $meta,
                    'is_pending' => $isPending,
                    'date' => $dateStr,
                    'day_name' => $date->translatedFormat('D'),
                    'day_number' => $date->day
                ];
            }
            $matrix[] = $userRow;
        }

        return $matrix;
    }
}
