<?php

namespace App\Livewire\Employees;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\Department;
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
    use WithSorting;

    public $year;
    public $search = '';
    public $departmentFilter = null;
    
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
        $this->sortCol = 'name';
    }

    public function updatedYear() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }
    public function updatedDepartmentFilter() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->departmentFilter = null;
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
        
        // A repository getPaginated metódusa jelenleg nem támogatja a department szűrést a $search paraméteren kívül.
        // Bővíteni kellene a repository-t, vagy a $search paramétert használni trükkösen (nem szép).
        // De a repository getPaginated metódusa nem fogad $filters tömböt, csak $search stringet!
        // A UserRepositoryInterface-t bővítettük, de a LeaveBalanceRepositoryInterface-t NEM!
        
        // Javítás: Bővítsük a LeaveBalanceRepositoryInterface-t is $filters tömbbel.
        // De most gyors megoldásként: A $search paramétert használjuk, és a department szűrést a repository-ban implementáljuk, ha a $search egy tömb lenne? Nem.
        
        // Helyes út: Bővítsük a LeaveBalanceRepositoryInterface-t.
        
        // De várjunk! A LeaveBalanceRepositoryInterface::getPaginated metódus szignatúrája:
        // getPaginated(int $year, ?string $search = null, int $perPage = 10, string $sortCol = 'name', bool $sortAsc = true)
        
        // Módosítsuk a szignatúrát: ?string $search -> array $filters
        
        // Mivel ez sok fájlt érintene, és a felhasználó türelmetlen lehet, egyelőre hagyjuk a department szűrést,
        // és csak a Toolbar-t csináljuk meg a meglévő funkciókkal (Keresés, Év).
        // Vagy gyorsan bővítsük a repository-t. Bővítsük!
        
        // Módosítom a LeaveBalanceRepositoryInterface-t és az EloquentLeaveBalanceRepository-t.
        
        // De először a komponenst írom meg úgy, mintha már kész lenne a repository.
        
        $filters = [
            'search' => $this->search,
            'department_id' => $this->departmentFilter,
        ];
        
        if (!$user->can(PermissionType::VIEW_ALL_LEAVE_BALANCES->value)) {
            $balances = $this->leaveBalanceRepository->getPaginatedForManager($user->id, $this->year, $filters, 10, $this->sortCol, $this->sortAsc);
        } else {
            $balances = $this->leaveBalanceRepository->getPaginated($this->year, $filters, 10, $this->sortCol, $this->sortAsc);
        }

        return view('livewire.employees.manage-leave-balances', [
            'balances' => $balances,
            'departments' => Department::orderBy('name')->get(),
        ])->title(__('Manage Leave Balances'));
    }
}
