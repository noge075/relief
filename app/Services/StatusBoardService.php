<?php

namespace App\Services;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PermissionType;
use App\Enums\RoleType;
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
        // 1. Dolgozók lekérése
        $users = \App\Models\User::where('is_active', true)
            ->when($filters['department_id'] ?? null, function ($q, $id) {
                $q->where('department_id', $id);
            })
            ->when($filters['manager_id'] ?? null, function ($q, $id) {
                $q->where('manager_id', $id);
            })
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name')
            ->get();

        // 2. Ünnepnapok és munkanapok
        $holidays = $this->holidayService->getHolidaysInRange($start, $end);
        $extraWorkdays = $this->holidayService->getExtraWorkdaysInRange($start, $end);

        // 3. Szabadságok lekérése (APPROVED és PENDING)
        $leaveRequests = \App\Models\LeaveRequest::whereIn('user_id', $users->pluck('id'))
            ->whereIn('status', [LeaveStatus::APPROVED->value, LeaveStatus::PENDING->value])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end->format('Y-m-d'))
                      ->where('end_date', '>=', $start->format('Y-m-d'));
            })
            ->get()
            ->groupBy('user_id');

        // 4. Mátrix összeállítása
        $matrix = [];
        $period = CarbonPeriod::create($start, $end);

        foreach ($users as $user) {
            // Jogosultság ellenőrzés a részletekhez
            // Láthatja, ha: Saját maga, VAGY a managere, VAGY van 'view leave request details' joga
            $canViewDetails = $actor->id === $user->id || 
                              $actor->id === $user->manager_id || 
                              $actor->can(PermissionType::VIEW_LEAVE_REQUEST_DETAILS->value);

            $userRow = [
                'user' => $user,
                'days' => []
            ];

            foreach ($period as $date) {
                $dateStr = $date->format('Y-m-d');
                $status = 'present'; // Alapértelmezett: Bent (Zöld)
                $meta = null;
                $isPending = false;

                // A. Ünnepnap / Hétvége ellenőrzés
                $isWeekend = $date->isWeekend();
                $isHoliday = isset($holidays[$dateStr]);
                $isExtraWorkday = isset($extraWorkdays[$dateStr]);

                if (($isWeekend || $isHoliday) && !$isExtraWorkday) {
                    $status = 'off'; // Nem munkanap (Szürke)
                    
                    if ($isHoliday) {
                        $meta = $holidays[$dateStr]['name'];
                    } elseif ($isWeekend) {
                        $meta = __('Weekend');
                    }
                }
                
                // Ha extra munkanap, és alapértelmezett státusz, akkor jelezzük
                if ($isExtraWorkday && $status === 'present') {
                    $meta = $extraWorkdays[$dateStr]['name'] ?? __('Extra Workday');
                }

                // B. Szabadság felülírása
                if (isset($leaveRequests[$user->id])) {
                    foreach ($leaveRequests[$user->id] as $request) {
                        if ($date->between($request->start_date, $request->end_date)) {
                            $status = $request->type->value; // vacation, sick, home_office
                            
                            // Ha láthatja a részleteket, akkor az indoklás, különben null (így a nézet a státusz nevét írja ki)
                            $meta = $canViewDetails ? ($request->reason ?: null) : null;

                            $isPending = $request->status === LeaveStatus::PENDING;
                            break; // Találtunk egyet, kilépünk
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
