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
    public $perPage = 10;
    
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

    protected $queryString = [
        'search' => ['except' => ''],
        'yearFilter' => ['except' => null],
        'departmentFilter' => ['except' => null],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'name'],
        'sortAsc' => ['except' => true],
    ];

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
        
        $this->perPage = request()->query('per_page', 10);
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedYearFilter() { $this->resetPage(); }
    public function updatedDepartmentFilter() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->departmentFilter = null;
        $this->yearFilter = Carbon::now()->year;
        $this->perPage = 10;
        $this->resetPage();
    }

    public function create()
    {
        $this->authorize(PermissionType::ADJUST_LEAVE_BALANCES->value);
        $this->resetForm();
        $this->year = $this->yearFilter;
        $this->users = $this->userRepository->getUsersWithoutLeaveBalance($this->year);
        
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
            ['search' => $this->search, 'department_id' => $this->departmentFilter],
            (int) $this->perPage,
            $this->sortCol,
            $this->sortAsc
        );

        $balances->appends([
            'search' => $this->search,
            'per_page' => $this->perPage,
            'departmentFilter' => $this->departmentFilter,
            'yearFilter' => $this->yearFilter,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.employees.manage-leave-balances', [
            'balances' => $balances,
            'departments' => Department::all(),
        ])->title(__('Leave Balances'));
    }
}
