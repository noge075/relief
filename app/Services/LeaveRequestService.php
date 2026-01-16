<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\User;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveRequestService
{
    public function __construct(
        protected LeaveRequestRepositoryInterface $leaveRequestRepository,
        protected LeaveBalanceRepositoryInterface $leaveBalanceRepository
    ) {}

    public function createRequest(User $user, array $data)
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
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

        // 2. HO szabály vizsgálat
        if ($type === LeaveType::HOME_OFFICE) {
            $this->validateHomeOfficeLimit($user, $startDate, $endDate);
        }

        // 3. Szabadság keret vizsgálat (csak ha Vacation)
        if ($type === LeaveType::VACATION) {
            $daysCount = $this->calculateWorkingDays($startDate, $endDate);
            $this->validateLeaveBalance($user, $daysCount, $startDate->year);
        }

        // Mentés
        $data['user_id'] = $user->id;
        $data['status'] = LeaveStatus::PENDING->value;
        $data['days_count'] = $startDate->diffInDays($endDate) + 1; // Egyszerűsített

        return $this->leaveRequestRepository->create($data);
    }

    public function updateRequest(User $user, int $id, array $data)
    {
        $request = $this->leaveRequestRepository->find($id);

        if (!$request || $request->user_id !== $user->id) {
            throw new \Exception(__('Request not found or access denied.'));
        }

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $type = LeaveType::from($data['type']);

        // 1. Átfedés vizsgálat (kivéve saját magát)
        $overlapping = $this->leaveRequestRepository->findOverlapping(
            $user->id,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
            $id // excludeId
        );

        if ($overlapping->isNotEmpty()) {
            throw ValidationException::withMessages([
                'date' => __('You already have a request for this period.')
            ]);
        }

        // 2. HO szabály vizsgálat
        if ($type === LeaveType::HOME_OFFICE) {
            $this->validateHomeOfficeLimit($user, $startDate, $endDate);
        }

        // 3. Szabadság keret vizsgálat (csak ha Vacation)
        if ($type === LeaveType::VACATION) {
            $daysCount = $this->calculateWorkingDays($startDate, $endDate);
            $this->validateLeaveBalance($user, $daysCount, $startDate->year, $id);
        }

        // Adatok frissítése
        $data['status'] = LeaveStatus::PENDING->value; // Újra jóváhagyás kell
        $data['days_count'] = $startDate->diffInDays($endDate) + 1;
        $data['manager_comment'] = null;
        $data['approver_id'] = null;

        return $this->leaveRequestRepository->update($id, $data);
    }

    protected function validateHomeOfficeLimit(User $user, Carbon $start, Carbon $end)
    {
        $daysRequested = $start->diffInDays($end) + 1;
        if ($daysRequested > 1) {
             // throw ValidationException::withMessages(['type' => 'Only 1 day of Home Office is allowed per request.']);
        }

        $checkStart = $start->copy()->subDays(13);
        $checkEnd = $start->copy()->subDay();

        $pastHO = $this->leaveRequestRepository->getForUserInPeriod($user->id, $checkStart->format('Y-m-d'), $checkEnd->format('Y-m-d'))
            ->where('type', LeaveType::HOME_OFFICE)
            ->whereIn('status', [LeaveStatus::APPROVED, LeaveStatus::PENDING]);

        if ($pastHO->isNotEmpty()) {
             // throw ValidationException::withMessages(['type' => __('Home Office limit exceeded (1 day / 2 weeks).')]);
        }
    }

    protected function validateLeaveBalance(User $user, int $daysCount, int $year, ?int $excludeRequestId = null)
    {
        $balance = $this->leaveBalanceRepository->getBalance($user->id, $year, LeaveType::VACATION->value);

        if (!$balance) {
             throw ValidationException::withMessages([
                'type' => __('No leave balance found for this year.')
            ]);
        }

        // Lekérjük a függő kérelmeket
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
        return $start->diffInDaysFiltered(function (Carbon $date) {
            return !$date->isWeekend();
        }, $end) + 1;
    }
    
    public function deleteRequest(int $id, int $userId)
    {
        $request = $this->leaveRequestRepository->find($id);
        
        if (!$request || $request->user_id !== $userId) {
            throw new \Exception(__('Request not found or access denied.'));
        }
        
        if ($request->status === LeaveStatus::APPROVED) {
            // Egyszerűsítve: törölhető.
        }
        
        return $this->leaveRequestRepository->delete($id);
    }
}
