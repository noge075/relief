<?php

namespace App\Livewire\Employees;

use App\Enums\PermissionType;
use App\Models\LeaveBalance;
use App\Models\User;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageLeaveBalances extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $year;
    public $search = '';
    
    // Modal
    public $showModal = false;
    public $editingBalanceId = null;
    public $userId;
    public $userName = ''; // Csak megjelenítésre editnél
    public $allowance = 0;
    public $used = 0;
    
    public $users = []; // Selecthez

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
        $this->authorize(PermissionType::ADJUST_LEAVE_BALANCES->value);
        $this->year = Carbon::now()->year;
    }

    public function updatedYear()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['editingBalanceId', 'userId', 'allowance', 'used']);
        $this->allowance = 20; // Default
        $this->used = 0;
        
        // Csak azokat töltjük be, akiknek nincs kerete az adott évben
        $this->users = $this->userRepository->getUsersWithoutLeaveBalance($this->year);
        
        // Alapértelmezetten kiválasztjuk az elsőt, hogy ne legyen üres a select
        if ($this->users->isNotEmpty()) {
            $this->userId = $this->users->first()->id;
        }
        
        $this->showModal = true;
    }

    public function edit($id)
    {
        $balance = $this->leaveBalanceRepository->find($id);
        
        // Jogosultság ellenőrzés
        if (!auth()->user()->can(PermissionType::VIEW_ALL_LEAVE_BALANCES->value)) {
            // Ha nem láthat mindent, ellenőrizzük, hogy a saját beosztottja-e
            if ($balance->user->manager_id !== auth()->id()) {
                abort(403);
            }
        }

        if ($balance) {
            $this->editingBalanceId = $balance->id;
            $this->userId = $balance->user_id;
            $this->userName = $balance->user->name;
            $this->allowance = $balance->allowance;
            $this->used = $balance->used;
            $this->showModal = true;
        }
    }

    public function save()
    {
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
            // Create
            $exists = $this->leaveBalanceRepository->hasBalance($this->userId, $this->year, 'vacation');
            
            if ($exists) {
                $this->addError('userId', __('This user already has a leave balance for this year.'));
                return;
            }

            $this->leaveBalanceRepository->create([
                'user_id' => $this->userId,
                'year' => $this->year,
                'type' => 'vacation', // Egyelőre fix
                'allowance' => $this->allowance,
                'used' => $this->used,
            ]);
            Flux::toast(__('Leave balance created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function render()
    {
        $user = auth()->user();
        
        if (!$user->can(PermissionType::VIEW_ALL_LEAVE_BALANCES->value)) {
            $balances = $this->leaveBalanceRepository->getPaginatedForManager($user->id, $this->year, $this->search);
        } else {
            $balances = $this->leaveBalanceRepository->getPaginated($this->year, $this->search);
        }

        return view('livewire.employees.manage-leave-balances', [
            'balances' => $balances
        ])->title(__('Manage Leave Balances'));
    }
}
