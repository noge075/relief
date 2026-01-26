<?php

namespace App\Livewire;

use App\Enums\PermissionType;
use App\Enums\RoleType;
use App\Models\Department;
use App\Models\User;
use App\Services\StatusBoardService;
use Carbon\Carbon;
use Livewire\Attributes\Title;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class StatusBoard extends Component
{
    use AuthorizesRequests;

    public $startDate;
    public $endDate;
    public $search = '';
    
    // Filters
    public $departmentId = null;
    public $managerId = null;

    protected StatusBoardService $statusBoardService;

    public function boot(StatusBoardService $statusBoardService)
    {
        $this->statusBoardService = $statusBoardService;
    }

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_STATUS_BOARD->value);
        $this->thisWeek();
    }

    public function placeholder()
    {
        return view('livewire.placeholders.status-board');
    }

    public function updatedStartDate()
    {
        if ($this->endDate && $this->startDate > $this->endDate) {
            $this->endDate = $this->startDate;
        }
    }

    public function updatedEndDate()
    {
        if ($this->startDate && $this->endDate < $this->startDate) {
            $this->startDate = $this->endDate;
        }
    }

    public function prevPeriod()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diff = $start->diffInDays($end) + 1;

        $this->startDate = $start->subDays($diff)->format('Y-m-d');
        $this->endDate = $end->subDays($diff)->format('Y-m-d');
    }

    public function nextPeriod()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diff = $start->diffInDays($end) + 1;

        $this->startDate = $start->addDays($diff)->format('Y-m-d');
        $this->endDate = $end->addDays($diff)->format('Y-m-d');
    }
    
    public function thisWeek()
    {
        $this->startDate = Carbon::now()->startOfWeek()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfWeek()->format('Y-m-d');
    }

    public function previousWeek()
    {
        $this->startDate = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
        $this->endDate = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
    }

    public function thisMonth()
    {
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function previousMonth()
    {
        $this->startDate = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
    }

    public function clearFilters()
    {
        $this->departmentId = null;
        $this->managerId = null;
        $this->search = '';
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $filters = [];
        if ($this->departmentId) $filters['department_id'] = $this->departmentId;
        if ($this->managerId) $filters['manager_id'] = $this->managerId;
        if ($this->search) $filters['search'] = $this->search;

        $matrix = $this->statusBoardService->getStatusMatrix(auth()->user(), $start, $end, $filters);

        // Statisztika számítása a megjelenített időszakra
        $stats = [
            'pending' => 0,
            'approved' => 0,
        ];

        foreach ($matrix as $row) {
            foreach ($row['days'] as $day) {
                if ($day['status'] !== 'present' && $day['status'] !== 'off') {
                    if ($day['is_pending'] ?? false) {
                        $stats['pending']++;
                    } else {
                        $stats['approved']++;
                    }
                }
            }
        }

        return view('livewire.status-board', [
            'matrix' => $matrix,
            'stats' => $stats,
            'departments' => Department::orderBy('name')->get(),
            'managers' => User::role(RoleType::MANAGER->value)->orderBy('last_name')->orderBy('first_name')->get(),
            'periodStart' => $start,
            'periodEnd' => $end,
        ])->title(__('Status Board'));
    }
}
