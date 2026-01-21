<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\RoleType;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LeaveRequestApprovedNotification;
use App\Notifications\LeaveRequestRejectedNotification;
use App\Notifications\LeaveRequestDeletedNotification;
use App\Notifications\NewLeaveRequestNotification;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification;

class LeaveRequestService
{
    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        protected HolidayService $holidayService,
        protected PayrollService $payrollService
    ) {}

    public function createRequest(User $user, array $data)
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        
        // Havi zárás ellenőrzése
        $this->validateMonthlyClosure($startDate);
        if ($startDate->month !== $endDate->month) {
            $this->validateMonthlyClosure($endDate);
        }

        $type = LeaveType::from($data['type']);
        
        // 1. Átfedés vizsgálat
        $overlapping = $this->leaveRequestRepository->findOverlapping(
            $user->id, 
            $startDate->format('Y-m-d'), 
            $endDate->format('Y-m-d')
        );

        if ($overlapping->isNotEmpty()) {
            throw ValidationException::withMessages([
                'date' => __('You already have a request for this period.')
            ]);
        }

        // 2. Munkanapok
        $daysCount = $this->calculateWorkingDays($startDate, $endDate);
        
        if ($daysCount === 0) {
            throw ValidationException::withMessages([
                'date' => __('The selected period contains no working days.')
            ]);
        }

        // 3. Keret
        if ($type === LeaveType::VACATION) {
            $this->validateLeaveBalance($user, $daysCount, $startDate->year);
        }

        // --- WARNINGS ---
        $warnings = [];

        if ($type === LeaveType::HOME_OFFICE) {
            $hoWarning = $this->checkHomeOfficeLimit($user, $startDate, $endDate);
            if ($hoWarning) {
                $warnings[] = $hoWarning;
            }
        }

        $deptWarning = $this->checkDepartmentOverlap($user, $startDate, $endDate, $type);
        if ($deptWarning) {
            $warnings[] = $deptWarning;
        }

        // Mentés
        $data['user_id'] = $user->id;
        $data['status'] = LeaveStatus::PENDING->value;
        $data['days_count'] = $daysCount;
        
        if (!empty($warnings)) {
            $data['has_warning'] = true;
            $data['warning_message'] = implode(' | ', $warnings);
        }

        $request = $this->leaveRequestRepository->create($data);

        // Értesítés a Managernek vagy HR/Adminnak
        if ($user->manager) {
            Log::info('Sending NewLeaveRequestNotification to manager: ' . $user->manager->email);
            $user->manager->notify(new NewLeaveRequestNotification($request));
        } else {
            // Ha nincs manager, értesítsük a HR-t és a Super Admint
            Log::warning('User ' . $user->email . ' has no manager assigned. Notifying HR/Super Admins.');
            $recipients = User::role([RoleType::HR->value, RoleType::SUPER_ADMIN->value])
                                ->where('id', '!=', $user->id) // Ne értesítse saját magát, ha ő is HR/Admin
                                ->get();
            Notification::send($recipients, new NewLeaveRequestNotification($request));
        }

        return $request;
    }

    public function updateRequest(User $user, int $id, array $data)
    {
        $request = $this->leaveRequestRepository->find($id);

        if (!$request || $request->user_id !== $user->id) {
            throw new \Exception(__('Request not found or access denied.'));
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        
        // Havi zárás ellenőrzése
        $this->validateMonthlyClosure($request->start_date);
        if ($request->start_date->month !== $request->end_date->month) {
            $this->validateMonthlyClosure($request->end_date);
        }
        $this->validateMonthlyClosure($startDate);
        if ($startDate->month !== $endDate->month) {
            $this->validateMonthlyClosure($endDate);
        }

        $type = LeaveType::from($data['type']);

        // 1. Átfedés
        $overlapping = $this->leaveRequestRepository->findOverlapping(
            $user->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $id
        );

        if ($overlapping->isNotEmpty()) {
            throw ValidationException::withMessages([
                'date' => __('You already have a request for this period.')
            ]);
        }

        // 2. Munkanapok
        $daysCount = $this->calculateWorkingDays($startDate, $endDate);
        
        if ($daysCount === 0) {
            throw ValidationException::withMessages([
                'date' => __('The selected period contains no working days.')
            ]);
        }

        // 3. Keret
        if ($type === LeaveType::VACATION) {
            $this->validateLeaveBalance($user, $daysCount, $startDate->year, $id);
        }

        // --- WARNINGS ---
        $warnings = [];

        if ($type === LeaveType::HOME_OFFICE) {
            $hoWarning = $this->checkHomeOfficeLimit($user, $startDate, $endDate);
            if ($hoWarning) {
                $warnings[] = $hoWarning;
            }
        }

        $deptWarning = $this->checkDepartmentOverlap($user, $startDate, $endDate, $type, $id);
        if ($deptWarning) {
            $warnings[] = $deptWarning;
        }

        // Adatok frissítése
        $data['status'] = LeaveStatus::PENDING->value;
        $data['days_count'] = $daysCount;
        $data['manager_comment'] = null;
        $data['approver_id'] = null;
        
        if (!empty($warnings)) {
            $data['has_warning'] = true;
            $data['warning_message'] = implode(' | ', $warnings);
        } else {
            $data['has_warning'] = false;
            $data['warning_message'] = null;
        }

        $this->leaveRequestRepository->update($id, $data);
        
        // Frissítjük a request objektumot a visszaadáshoz
        $request->fill($data);

        // Értesítés a Managernek
        if ($user->manager) {
            Log::info('Sending NewLeaveRequestNotification (update) to manager: ' . $user->manager->email);
            $user->manager->notify(new NewLeaveRequestNotification($request));
        } else {
            // Ha nincs manager, értesítsük a HR-t és a Super Admint
            Log::warning('User ' . $user->email . ' has no manager assigned. Notifying HR/Super Admins about update.');
            $recipients = User::role([RoleType::HR->value, RoleType::SUPER_ADMIN->value])
                                ->where('id', '!=', $user->id)
                                ->get();
            Notification::send($recipients, new NewLeaveRequestNotification($request));
        }

        return $request;
    }

    public function approveRequest(int $id, User $approver)
    {
        return DB::transaction(function () use ($id, $approver) {
            $request = $this->leaveRequestRepository->find($id);

            if (!$request) {
                throw new \Exception(__('Request not found.'));
            }
            
            $this->validateMonthlyClosure($request->start_date);

            $this->leaveRequestRepository->updateStatus($request, LeaveStatus::APPROVED->value);

            if ($request->type === LeaveType::VACATION) {
                $this->leaveBalanceRepository->incrementUsed(
                    $request->user_id,
                    $request->start_date->year,
                    LeaveType::VACATION->value,
                    $request->days_count
                );
            }
            
            // Értesítés a dolgozónak
            Log::info('Sending LeaveRequestApprovedNotification to user: ' . $request->user->email);
            $request->user->notify(new LeaveRequestApprovedNotification($request));
            
            return true;
        });
    }

    public function rejectRequest(int $id, User $approver, string $comment)
    {
        $request = $this->leaveRequestRepository->find($id);

        if (!$request) {
            throw new \Exception(__('Request not found.'));
        }
        
        $this->validateMonthlyClosure($request->start_date);

        $updated = $this->leaveRequestRepository->updateStatus($request, LeaveStatus::REJECTED->value, $comment);
        
        // Értesítés a dolgozónak
        if ($updated) {
            Log::info('Sending LeaveRequestRejectedNotification to user: ' . $request->user->email);
            $request->user->notify(new LeaveRequestRejectedNotification($request));
        }

        return $updated;
    }
    
    public function deleteRequest(int $id, int $userId)
    {
        $request = $this->leaveRequestRepository->find($id);
        
        if (!$request || $request->user_id !== $userId) {
            throw new \Exception(__('Request not found or access denied.'));
        }
        
        $this->validateMonthlyClosure($request->start_date);
        
        if ($request->status === LeaveStatus::APPROVED) {
            // Egyszerűsítve: törölhető.
        }
        
        $this->leaveRequestRepository->delete($id);

        // Értesítés a Managernek vagy HR/Adminnak
        if ($request->user->manager) {
            Log::info('Sending LeaveRequestDeletedNotification to manager: ' . $request->user->manager->email);
            $request->user->manager->notify(new LeaveRequestDeletedNotification($request));
        } else {
            Log::warning('User ' . $request->user->email . ' has no manager assigned. Notifying HR/Super Admins about deletion.');
            $recipients = User::role([RoleType::HR->value, RoleType::SUPER_ADMIN->value])
                                ->where('id', '!=', $request->user->id)
                                ->get();
            Notification::send($recipients, new LeaveRequestDeletedNotification($request));
        }
        
        return true;
    }

    protected function validateMonthlyClosure($date)
    {
        if ($this->payrollService->isMonthClosed($date->year, $date->month)) {
            throw ValidationException::withMessages([
                'date' => __('This month is closed and cannot be modified.')
            ]);
        }
    }

    protected function checkHomeOfficeLimit(User $user, Carbon $start, Carbon $end): ?string
    {
        $limitDays = (int) (Setting::where('key', 'ho_limit_days')->value('value') ?? 1);
        $limitPeriod = (int) (Setting::where('key', 'ho_limit_period')->value('value') ?? 14);

        $daysRequested = $start->diffInDays($end) + 1;
        if ($daysRequested > $limitDays) {
             return __('Home Office limit exceeded: Only :limit day(s) allowed per request.', ['limit' => $limitDays]);
        }

        $checkStart = $start->copy()->subDays($limitPeriod - 1);
        $checkEnd = $start->copy()->subDay();

        $pastHO = $this->leaveRequestRepository->getForUserInPeriod($user->id, $checkStart->format('Y-m-d'), $checkEnd->format('Y-m-d'))
            ->where('type', LeaveType::HOME_OFFICE)
            ->whereIn('status', [LeaveStatus::APPROVED, LeaveStatus::PENDING]);

        $pastDaysCount = $pastHO->sum('days_count');

        if (($pastDaysCount + $daysRequested) > $limitDays) {
             return __('Home Office limit exceeded: :limit day(s) / :period days.', ['limit' => $limitDays, 'period' => $limitPeriod]);
        }
        
        return null;
    }
    
    protected function checkDepartmentOverlap(User $user, Carbon $start, Carbon $end, LeaveType $type, ?int $excludeId = null): ?string
    {
        if (!$user->department_id) {
            return null;
        }

        $colleagues = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->pluck('id');

        if ($colleagues->isEmpty()) {
            return null;
        }

        $overlaps = \App\Models\LeaveRequest::whereIn('user_id', $colleagues)
            ->whereIn('status', [LeaveStatus::APPROVED->value, LeaveStatus::PENDING->value])
            ->where('type', $type->value)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->count();

        if ($overlaps > 0) {
            return __('Department overlap: :count colleague(s) also absent.', ['count' => $overlaps]);
        }

        return null;
    }

    protected function validateLeaveBalance(User $user, int $daysCount, int $year, ?int $excludeRequestId = null)
    {
        $balance = $this->leaveBalanceRepository->getBalance($user->id, $year, LeaveType::VACATION->value);

        if (!$balance) {
             throw ValidationException::withMessages([
                'type' => __('No leave balance found for this year.')
            ]);
        }

        $pendingRequests = $this->leaveRequestRepository->getForUser($user->id, LeaveStatus::PENDING->value);
        
        $pendingDays = 0;
        foreach ($pendingRequests as $req) {
            if ($req->type === LeaveType::VACATION && $req->start_date->year === $year) {
                if ($excludeRequestId && $req->id === $excludeRequestId) {
                    continue;
                }
                $pendingDays += $req->days_count;
            }
        }

        $remaining = $balance->allowance - $balance->used - $pendingDays;

        if ($remaining < $daysCount) {
            throw ValidationException::withMessages([
                'days_count' => __('Insufficient leave balance. Remaining: :count days.', ['count' => $remaining])
            ]);
        }
    }

    protected function calculateWorkingDays(Carbon $start, Carbon $end)
    {
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $holidayDates = array_keys($holidays);
        
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);
        $extraWorkdayDates = array_keys($extraWorkdays);

        return $start->diffInDaysFiltered(function (Carbon $date) use ($holidayDates, $extraWorkdayDates) {
            $dateStr = $date->format('Y-m-d');
            
            $isWeekend = $date->isWeekend();
            $isHoliday = in_array($dateStr, $holidayDates);
            $isExtraWorkday = in_array($dateStr, $extraWorkdayDates);
            
            if ($isExtraWorkday) {
                return true;
            }
            
            if ($isHoliday) {
                return false;
            }
            
            return !$isWeekend;
        }, $end) + 1;
    }
}
