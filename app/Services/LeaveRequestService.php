<?php

namespace App\Services;

use App\Enums\HomeOfficePolicyType;
use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PermissionType;
use App\Enums\RoleType;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Notifications\LeaveRequestApprovedNotification;
use App\Notifications\LeaveRequestDeletedNotification;
use App\Notifications\LeaveRequestRejectedNotification;
use App\Notifications\NewLeaveRequestNotification;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        protected HolidayService $holidayService,
        protected PayrollService $payrollService,
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Handle the creation of a new leave request.
     */
    public function createRequest(User $user, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $data) {
            $preparedData = $this->processRequestData($user, $data);

            $request = $this->leaveRequestRepository->create($preparedData);

            $this->notifyManagerOrAdmins($user, new NewLeaveRequestNotification($request));

            return $request;
        });
    }

    /**
     * Handle the update of an existing leave request.
     */
    public function updateRequest(User $user, int $id, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($user, $id, $data) {
            /**
             * @var LeaveRequest $request
             */
            $request = $this->leaveRequestRepository->find($id);

            if (!$request || $request->user_id !== $user->id) {
                throw new \Exception(__('Request not found or access denied.'));
            }

            if ($request->status !== LeaveStatus::PENDING) {
                throw new \Exception(__('Only pending requests can be updated.'));
            }

            $preparedData = $this->processRequestData($user, $data, $id);

            // Reset approval fields on update
            $preparedData['manager_comment'] = null;
            $preparedData['approver_id'] = null;

            $this->leaveRequestRepository->update($id, $preparedData);
            $request->refresh();

            $this->notifyManagerOrAdmins($user, new NewLeaveRequestNotification($request), true);

            return $request;
        });
    }

    /**
     * Process validation, calculation, and warnings for request data.
     */
    protected function processRequestData(User $user, array $data, ?int $requestId = null): array
    {
        [$startDate, $endDate] = $this->parseDates($data['start_date'], $data['end_date']);
        $type = LeaveType::from($data['type']);

        $this->validatePastDates($user, $startDate, $type);
        $this->validateMonthlyClosureForRange($startDate, $endDate);
        $this->validateOverlaps($user, $startDate, $endDate, $requestId);

        $daysCount = $this->calculateWorkingDays($startDate, $endDate);
        if ($daysCount === 0) {
            throw ValidationException::withMessages(['date' => __('The selected period contains no working days.')]);
        }

        if ($type === LeaveType::VACATION) {
            $this->validateLeaveBalance($user, $daysCount, $startDate->year, $requestId);
        }

        $warnings = $this->generateWarnings($user, $startDate, $endDate, $type, $requestId);

        return array_merge($data, [
            'user_id'         => $user->id,
            'status'          => LeaveStatus::PENDING->value,
            'days_count'      => $daysCount,
            'has_warning'     => !empty($warnings),
            'warning_message' => !empty($warnings) ? implode(' | ', $warnings) : null,
        ]);
    }

    public function approveRequest(int $id, User $approver): bool
    {
        return DB::transaction(function () use ($id, $approver) {
            /**
             * @var LeaveRequest $request
             */
            $request = $this->leaveRequestRepository->find($id);
            $this->ensureRequestExists($request);
            $this->validateMonthlyClosure($request->start_date);

            $this->leaveRequestRepository->updateStatus($request, LeaveStatus::APPROVED->value, null, $approver->id);

            if ($request->type === LeaveType::VACATION) {
                $this->leaveBalanceRepository->incrementUsed(
                    $request->user_id,
                    $request->start_date->year,
                    LeaveType::VACATION->value,
                    $request->days_count
                );
            }

            // Generate attendance logs for the leave period
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                $this->attendanceService->generateLogForUser($request->user, $date);
            }

            activity()
                ->performedOn($request)
                ->causedBy($approver)
                ->event('approved')
                ->log('Leave request approved');

            Log::info("Sending LeaveRequestApprovedNotification to user: {$request->user->email}");
            $request->user->notify(new LeaveRequestApprovedNotification($request));

            return true;
        });
    }

    /**
     * @throws \Exception
     */
    public function rejectRequest(int $id, User $approver, string $comment): bool
    {
        /**
         * @var LeaveRequest $request
         */
        $request = $this->leaveRequestRepository->find($id);
        $this->ensureRequestExists($request);
        $this->validateMonthlyClosure($request->start_date);

        $updated = $this->leaveRequestRepository->updateStatus($request, LeaveStatus::REJECTED->value, $comment, $approver->id);

        if ($updated) {
            activity()
                ->performedOn($request)
                ->causedBy($approver)
                ->event('rejected')
                ->withProperties(['comment' => $comment])
                ->log('Leave request rejected');

            Log::info("Sending LeaveRequestRejectedNotification to user: {$request->user->email}");
            $request->user->notify(new LeaveRequestRejectedNotification($request));
        }

        return $updated;
    }

    public function deleteRequest(int $id, int $userId): bool
    {
        $request = $this->leaveRequestRepository->find($id);

        if (!$request || $request->user_id !== $userId) {
            throw new \Exception(__('Request not found or access denied.'));
        }

        $this->validateMonthlyClosure($request->start_date);

        if ($request->status !== LeaveStatus::PENDING) {
            throw new \Exception(__('Only pending requests can be deleted.'));
        }

        $this->leaveRequestRepository->delete($id);

        activity()
            ->performedOn($request)
            ->causedBy(auth()->user())
            ->event('deleted')
            ->log('Leave request deleted');

        $this->notifyManagerOrAdmins($request->user, new LeaveRequestDeletedNotification($request));

        return true;
    }

    protected function validatePastDates(User $user, Carbon $startDate, LeaveType $type): void
    {
        if ($startDate->isPast() && !$startDate->isToday() && $type !== LeaveType::SICK) {
            if (!$user->can(PermissionType::CREATE_PAST_LEAVE_REQUESTS->value)) {
                throw ValidationException::withMessages([
                    'date' => __('Cannot create leave request for past dates (except sick leave).')
                ]);
            }
        }
    }

    protected function validateMonthlyClosureForRange(Carbon|CarbonImmutable $start, Carbon|CarbonImmutable $end): void
    {
        $this->validateMonthlyClosure($start);
        if ($start->month !== $end->month) {
            $this->validateMonthlyClosure($end);
        }
    }

    protected function validateMonthlyClosure(Carbon|CarbonImmutable $date): void
    {
        if ($this->payrollService->isMonthClosed($date->year, $date->month)) {
            throw ValidationException::withMessages([
                'date' => __('This month is closed and cannot be modified.')
            ]);
        }
    }

    protected function validateOverlaps(
        User $user,
        Carbon|CarbonImmutable $start,
        Carbon|CarbonImmutable $end,
        ?int $excludeId = null
    ): void {
        $overlapping = $this->leaveRequestRepository->findOverlapping(
            $user->id,
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $excludeId
        );

        if ($overlapping->isNotEmpty()) {
            throw ValidationException::withMessages([
                'date' => __('You already have a request for this period.')
            ]);
        }
    }

    protected function validateLeaveBalance(User $user, int $daysCount, int $year, ?int $excludeRequestId = null): void
    {
        $balance = $this->leaveBalanceRepository->getBalance($user->id, $year, LeaveType::VACATION->value);

        if (!$balance) {
            throw ValidationException::withMessages(['type' => __('No leave balance found for this year.')]);
        }

        // Calculate pending days excluding the current request being updated
        $pendingRequests = $this->leaveRequestRepository->getForUser($user->id, LeaveStatus::PENDING->value);

        $pendingDays = $pendingRequests
            ->filter(fn ($req) => $req->type === LeaveType::VACATION
                && $req->start_date->year === $year
                && (!$excludeRequestId || $req->id !== $excludeRequestId))
            ->sum('days_count');

        $remaining = $balance->allowance - $balance->used - $pendingDays;

        if ($remaining < $daysCount) {
            throw ValidationException::withMessages([
                'days_count' => __('Insufficient leave balance. Remaining: :count days.', ['count' => $remaining])
            ]);
        }
    }

    protected function generateWarnings(User $user, Carbon $start, Carbon $end, LeaveType $type, ?int $excludeId): array
    {
        $warnings = [];

        if ($type === LeaveType::HOME_OFFICE) {
            if ($msg = $this->checkHomeOfficeLimit($user, $start, $end)) {
                $warnings[] = $msg;
            }
        }

        if ($msg = $this->checkDepartmentOverlap($user, $start, $end, $type, $excludeId)) {
            $warnings[] = $msg;
        }

        return $warnings;
    }

    protected function checkHomeOfficeLimit(User $user, Carbon $start, Carbon $end): ?string
    {
        $policy = $user->homeOfficePolicy;

        if (!$policy) {
            return __('No Home Office policy assigned.');
        }

        switch ($policy->type) {
            case HomeOfficePolicyType::NONE:
                return __('Home Office is not allowed based on your policy.');

            case HomeOfficePolicyType::LIMITED:
                $limitDays = $policy->limit_days;
                $periodDays = $policy->period_days;

                $daysRequested = $this->calculateWorkingDays($start, $end);

                $checkStart = $start->copy()->subDays($periodDays - 1);
                $checkEnd = $end;

                $pastDaysCount = $this->leaveRequestRepository
                    ->getForUserInPeriod($user->id, $checkStart->format('Y-m-d'), $checkEnd->format('Y-m-d'))
                    ->where('type', LeaveType::HOME_OFFICE)
                    ->whereIn('status', [LeaveStatus::APPROVED, LeaveStatus::PENDING])
                    ->sum('days_count');

                if (($pastDaysCount + $daysRequested) > $limitDays) {
                    return __('Home Office limit exceeded: :limit day(s) / :period days.', ['limit' => $limitDays, 'period' => $periodDays]);
                }
                break;

            case HomeOfficePolicyType::FLEXIBLE:
            case HomeOfficePolicyType::FULL_REMOTE:
                // No limit check needed
                break;
        }

        return null;
    }

    protected function checkDepartmentOverlap(User $user, Carbon $start, Carbon $end, LeaveType $type, ?int $excludeId = null): ?string
    {
        $userDepartmentIds = $user->departments()->pluck('id');

        if ($userDepartmentIds->isEmpty()) {
            return null;
        }

        $colleagueIds = User::whereHas('departments', function ($query) use ($userDepartmentIds) {
            $query->whereIn('departments.id', $userDepartmentIds);
        })
        ->where('id', '!=', $user->id)
        ->pluck('id');

        if ($colleagueIds->isEmpty()) {
            return null;
        }

        $overlaps = LeaveRequest::whereIn('user_id', $colleagueIds)
            ->whereIn('status', [LeaveStatus::APPROVED->value, LeaveStatus::PENDING->value])
            ->where('type', $type->value)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->count();


        return $overlaps > 0
            ? __('Department overlap: :count colleague(s) also absent.', ['count' => $overlaps])
            : null;
    }


    protected function notifyManagerOrAdmins(User $user, $notification, bool $isUpdate = false): void
    {
        $context = $isUpdate ? 'update' : 'creation';

        if ($user->manager) {
            Log::info("Sending notification ({$context}) to manager: {$user->manager->email}");
            $user->manager->notify($notification);
        } else {
            Log::warning("User {$user->email} has no manager. Notifying HR/Super Admins ({$context}).");
            $recipients = User::role([RoleType::HR->value, RoleType::SUPER_ADMIN->value])
                ->where('id', '!=', $user->id)
                ->get();
            Notification::send($recipients, $notification);
        }
    }

    protected function calculateWorkingDays(Carbon $start, Carbon $end): int
    {
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        $holidayDates = array_keys($holidays);
        $extraWorkdayDates = array_keys($extraWorkdays);

        return $start->diffInDaysFiltered(function (Carbon $date) use ($holidayDates, $extraWorkdayDates) {
                $dateStr = $date->format('Y-m-d');

                if (in_array($dateStr, $extraWorkdayDates)) return true;
                if (in_array($dateStr, $holidayDates)) return false;

                return !$date->isWeekend();
            }, $end) + 1;
    }

    protected function parseDates(string $start, string $end): array
    {
        return [Carbon::parse($start), Carbon::parse($end)];
    }

    protected function ensureRequestExists(?LeaveRequest $request): void
    {
        if (!$request) {
            throw new \Exception(__('Request not found.'));
        }
    }
}
