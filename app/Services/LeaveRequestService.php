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
        protected LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        protected HolidayService $holidayService
    ) {}

    public function createRequest(User $user, array $data)
    {
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $type = LeaveType::from($data['type']);
        
        // 1. Átfedés vizsgálat (Saját magával) - Ez HIBA, nem warning
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

        // 2. Munkanapok számítása és validálása - Ez is HIBA
        $daysCount = $this->calculateWorkingDays($startDate, $endDate);
        
        if ($daysCount === 0) {
            throw ValidationException::withMessages([
                'date' => __('The selected period contains no working days.')
            ]);
        }

        // 3. Szabadság keret vizsgálat (csak ha Vacation) - Ez is HIBA
        if ($type === LeaveType::VACATION) {
            $this->validateLeaveBalance($user, $daysCount, $startDate->year);
        }

        // --- WARNINGS ---
        $warnings = [];

        // 4. HO szabály vizsgálat
        if ($type === LeaveType::HOME_OFFICE) {
            $hoWarning = $this->checkHomeOfficeLimit($user, $startDate, $endDate);
            if ($hoWarning) {
                $warnings[] = $hoWarning;
            }
        }

        // 5. Részleg átfedés vizsgálat
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

        // 1. Átfedés vizsgálat
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

        return $this->leaveRequestRepository->update($id, $data);
    }

    public function approveRequest(int $id, User $approver)
    {
        return DB::transaction(function () use ($id, $approver) {
            $request = $this->leaveRequestRepository->find($id);

            if (!$request) {
                throw new \Exception(__('Request not found.'));
            }

            $this->leaveRequestRepository->updateStatus($request, LeaveStatus::APPROVED->value);

            if ($request->type === LeaveType::VACATION) {
                $this->leaveBalanceRepository->incrementUsed(
                    $request->user_id,
                    $request->start_date->year,
                    LeaveType::VACATION->value,
                    $request->days_count
                );
            }
            
            return true;
        });
    }

    public function rejectRequest(int $id, User $approver, string $comment)
    {
        $request = $this->leaveRequestRepository->find($id);

        if (!$request) {
            throw new \Exception(__('Request not found.'));
        }

        return $this->leaveRequestRepository->updateStatus($request, LeaveStatus::REJECTED->value, $comment);
    }

    protected function checkHomeOfficeLimit(User $user, Carbon $start, Carbon $end): ?string
    {
        // Szabály: 2 hetente 1 nap (gördülő 14 nap)
        // Ez egy példa implementáció, a pontos szabályt finomítani lehet.
        
        $daysRequested = $start->diffInDays($end) + 1;
        if ($daysRequested > 1) {
             return __('Home Office limit exceeded: Only 1 day allowed per request.');
        }

        $checkStart = $start->copy()->subDays(13);
        $checkEnd = $start->copy()->subDay();

        $pastHO = $this->leaveRequestRepository->getForUserInPeriod($user->id, $checkStart->format('Y-m-d'), $checkEnd->format('Y-m-d'))
            ->where('type', LeaveType::HOME_OFFICE)
            ->whereIn('status', [LeaveStatus::APPROVED, LeaveStatus::PENDING]);

        if ($pastHO->isNotEmpty()) {
             return __('Home Office limit exceeded: 1 day / 2 weeks.');
        }
        
        return null;
    }
    
    protected function checkDepartmentOverlap(User $user, Carbon $start, Carbon $end, LeaveType $type, ?int $excludeId = null): ?string
    {
        if (!$user->department_id) {
            return null;
        }

        // Lekérjük a részleg többi dolgozóját
        $colleagues = User::where('department_id', $user->department_id)
            ->where('id', '!=', $user->id)
            ->pluck('id');

        if ($colleagues->isEmpty()) {
            return null;
        }

        // Lekérjük a kollégák átfedő kérelmeit
        // Ehhez a repository-t kellene bővíteni egy findOverlappingForUsers metódussal, vagy itt query-zni.
        // Mivel a repository interface-t nem akarom most módosítani, itt használok egy query-t (ami nem szép, de gyors).
        
        $overlaps = \App\Models\LeaveRequest::whereIn('user_id', $colleagues)
            ->whereIn('status', [LeaveStatus::APPROVED->value, LeaveStatus::PENDING->value])
            ->where('type', $type->value) // Csak azonos típusú (pl. szabi vs szabi)
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
