<?php

namespace App\Livewire\Employees;

use App\Enums\LeaveType;
use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\Department;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageLeaveBalances extends Component
{
    use WithPagination;
    use WithSorting;
    use AuthorizesRequests;

    public $search = '';
    public $yearFilter;
    public $departmentFilter = null;
    
    public $showModal = false;
    public $editingBalanceId = null;
    
    // Form
    public $userId;
    public $userName; // Csak megjelenítésre szerkesztéskor
    public $year;
    public $type = LeaveType::VACATION->value;
    public $allowance;
    public $used;

    public $users = []; // Dropdownhoz

    protected LeaveBalanceRepositoryInterface $leaveBalanceRepository;
    protected UserRepositoryInterface $userRepository;

    public function boot(
        LeaveBalanceRepositoryInterface $leaveBalanceRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->leaveBalanceRepository = $leaveBalanceRepository;
        $this->userRepository = $userRepository;
    }

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_ALL_LEAVE_BALANCES->value);
        $this->yearFilter = Carbon::now()->year;
        $this->sortCol = 'name';
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedYearFilter() { $this->resetPage(); }
    public function updatedDepartmentFilter() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->departmentFilter = null;
        $this->yearFilter = Carbon::now()->year;
        $this->resetPage();
    }

    public function create()
    {
        $this->authorize(PermissionType::ADJUST_LEAVE_BALANCES->value);
        $this->resetForm();
        $this->year = $this->yearFilter;
        $this->users = $this->userRepository->getUsersWithoutLeaveBalance($this->year);
        
        // Alapértelmezett user
        if ($this->users->isNotEmpty()) {
            $this->userId = $this->users->first()->id;
        }
        
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::ADJUST_LEAVE_BALANCES->value);
        $this->resetForm();
        $this->editingBalanceId = $id;
        
        $balance = $this->leaveBalanceRepository->find($id);
        
        $this->userId = $balance->user_id;
        $this->userName = $balance->user->name;
        $this->year = $balance->year;
        $this->type = $balance->type;
        $this->allowance = $balance->allowance;
        $this->used = $balance->used;

        $this->showModal = true;
    }

    public function save()
    {
        $this->authorize(PermissionType::ADJUST_LEAVE_BALANCES->value);

        $rules = [
            'allowance' => 'required|numeric|min:0',
            'used' => 'required|numeric|min:0',
        ];

        if (!$this->editingBalanceId) {
            $rules['userId'] = 'required|exists:users,id';
        }

        $this->validate($rules);

        if ($this->editingBalanceId) {
            $this->leaveBalanceRepository->update($this->editingBalanceId, [
                'allowance' => $this->allowance,
                'used' => $this->used,
            ]);
            Flux::toast(__('Leave balance updated successfully.'), variant: 'success');
        } else {
            // Ellenőrzés, hogy létezik-e már
            if ($this->leaveBalanceRepository->getBalance($this->userId, $this->year, $this->type)) {
                $this->addError('userId', __('This user already has a leave balance for this year.'));
                return;
            }

            $this->leaveBalanceRepository->create([
                'user_id' => $this->userId,
                'year' => $this->year,
                'type' => $this->type,
                'allowance' => $this->allowance,
                'used' => $this->used,
            ]);
            Flux::toast(__('Leave balance created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    private function resetForm()
    {
        $this->reset(['userId', 'userName', 'allowance', 'used', 'editingBalanceId']);
        $this->year = $this->yearFilter;
    }

    public function render()
    {
        $balances = $this->leaveBalanceRepository->getPaginated(
            $this->yearFilter,
            ['search' => $this->search, 'department_id' => $this->departmentFilter], // Átadjuk a szűrőt
            10, 
            $this->sortCol,
            $this->sortAsc
        );

        return view('livewire.employees.manage-leave-balances', [
            'balances' => $balances,
            'departments' => Department::all(),
        ])->title(__('Leave Balances'));
    }
}
