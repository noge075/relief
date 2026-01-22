<?php

namespace App\Livewire\Employees;

use App\Enums\EmploymentType;
use App\Enums\PermissionType;
use App\Enums\RoleType;
use App\Livewire\Traits\WithSorting;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\RoleService;
use App\Services\UserService;
use App\Models\Department;
use App\Models\User;
use App\Models\WorkSchedule;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageEmployees extends Component
{
    use WithPagination;
    use WithSorting;
    use AuthorizesRequests;

    public $showModal = false;
    public $isEditing = false;

    public $search = '';
    public $perPage = 10;

    // Filters
    public $departmentFilter = null;
    public $roleFilter = null;
    public $statusFilter = null;
    public $employmentTypeFilter = null;
    public $workScheduleFilter = null;

    public $editingId = null;

    // Form
    public $last_name = '';
    public $first_name = '';
    public $email = '';
    public $password = '';
    public $department_id = null;
    public $work_schedule_id = null;
    public $employment_type = null;
    public $role;
    public $id_card_number = '';
    public $tax_id = '';
    public $ssn = '';
    public $address = '';
    public $phone = '';
    
    public $selectedPermissions = [];
    public $rolePermissions = [];
    
    public $allPermissions;
    
    // Colors
    public $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'departmentFilter' => ['except' => null],
        'roleFilter' => ['except' => null],
        'statusFilter' => ['except' => null],
        'employmentTypeFilter' => ['except' => null],
        'workScheduleFilter' => ['except' => null],
        'sortCol' => ['except' => 'name'],
        'sortAsc' => ['except' => true],
    ];

    public function mount(RoleService $roleService)
    {
        $this->authorize(PermissionType::VIEW_USERS->value);
        $this->sortCol = 'name';
        $this->allPermissions = $roleService->getGroupedPermissions();
        $this->updateRolePermissions();

        $this->perPage = request()->query('per_page', 10);
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDepartmentFilter() { $this->resetPage(); }
    public function updatedRoleFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedEmploymentTypeFilter() { $this->resetPage(); }
    public function updatedWorkScheduleFilter() { $this->resetPage(); }

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function updatedRole()
    {
        $this->updateRolePermissions();
    }

    public function updateRolePermissions()
    {
        if ($this->role) {
            if ($this->role === RoleType::SUPER_ADMIN->value) {
                $allPermissionNames = [];
                foreach ($this->allPermissions as $group => $perms) {
                    foreach ($perms as $perm) {
                        $allPermissionNames[] = $perm->name;
                    }
                }
                $this->rolePermissions = $allPermissionNames;
            } else {
                $roleModel = Role::where('name', $this->role)->first();
                $this->rolePermissions = $roleModel ? $roleModel->permissions->pluck('name')->toArray() : [];
            }
        } else {
            $this->rolePermissions = [];
        }
    }
    
    public function toggleAllPermissions()
    {
        $allPermissionNames = [];
        foreach ($this->allPermissions as $group => $perms) {
            foreach ($perms as $perm) {
                $allPermissionNames[] = $perm->name;
            }
        }
        
        $toggleablePermissions = array_values(array_diff($allPermissionNames, $this->rolePermissions));
        $selectedToggleable = array_intersect($this->selectedPermissions, $toggleablePermissions);
        
        if (count($selectedToggleable) === count($toggleablePermissions)) {
            $this->selectedPermissions = [];
        } else {
            $this->selectedPermissions = $toggleablePermissions;
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->departmentFilter = null;
        $this->roleFilter = null;
        $this->statusFilter = null;
        $this->employmentTypeFilter = null;
        $this->workScheduleFilter = null;
        $this->perPage = 10;
        $this->resetPage();
    }
    
    public function canImpersonate(User $targetUser)
    {
        $currentUser = auth()->user();
        
        if ($currentUser->id === $targetUser->id) {
            return false;
        }
        
        if ($targetUser->hasRole(RoleType::SUPER_ADMIN->value) && !$currentUser->hasRole(RoleType::SUPER_ADMIN->value)) {
            return false;
        }
        
        if ($currentUser->can(PermissionType::VIEW_ALL_USERS->value)) {
            return true;
        }
        
        if ($currentUser->can(PermissionType::VIEW_USERS->value) && $targetUser->manager_id === $currentUser->id) {
            return true;
        }
        
        return false;
    }

    public function render(UserService $userService)
    {
        $filters = [
            'search' => $this->search,
            'department_id' => $this->departmentFilter,
            'role' => $this->roleFilter,
            'status' => $this->statusFilter,
            'employment_type' => $this->employmentTypeFilter,
            'work_schedule_id' => $this->workScheduleFilter,
        ];

        $users = $userService->getEmployeesList(auth()->user(), (int) $this->perPage, $filters, $this->sortCol, $this->sortAsc);

        $users->appends([
            'search' => $this->search,
            'per_page' => $this->perPage,
            'departmentFilter' => $this->departmentFilter,
            'roleFilter' => $this->roleFilter,
            'statusFilter' => $this->statusFilter,
            'employmentTypeFilter' => $this->employmentTypeFilter,
            'workScheduleFilter' => $this->workScheduleFilter,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.employees.manage-employees', [
            'users' => $users,
            'departments' => Department::all(),
            'schedules' => WorkSchedule::all(),
            'roles' => Role::all(),
            'employmentTypes' => EmploymentType::cases(),
        ])->title(__('Employees'));
    }

    public function openCreate()
    {
        $this->authorize(PermissionType::CREATE_USERS->value);
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id, UserRepositoryInterface $userRepository)
    {
        $this->authorize(PermissionType::EDIT_USERS->value);
        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $id;

        $user = $userRepository->find($id);

        $this->last_name = $user->last_name;
        $this->first_name = $user->first_name;
        $this->email = $user->email;
        $this->department_id = $user->department_id;
        $this->work_schedule_id = $user->work_schedule_id;
        $this->employment_type = $user->employment_type?->value;
        $this->role = $user->roles->first()?->name ?? null;
        
        $this->id_card_number = $user->id_card_number;
        $this->tax_id = $user->tax_id;
        $this->ssn = $user->ssn;
        $this->address = $user->address;
        $this->phone = $user->phone;
        
        $this->selectedPermissions = $user->getDirectPermissions()->pluck('name')->toArray();
        $this->updateRolePermissions();

        $this->showModal = true;
    }

    public function save(UserService $userService)
    {
        if ($this->isEditing) {
            $this->authorize(PermissionType::EDIT_USERS->value);
        } else {
            $this->authorize(PermissionType::CREATE_USERS->value);
        }

        $rules = [
            'last_name' => 'required|min:2',
            'first_name' => 'required|min:2',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            'department_id' => 'nullable|exists:departments,id',
            'work_schedule_id' => 'nullable|exists:work_schedules,id',
            'employment_type' => 'required',
            'role' => 'required|exists:roles,name',
            'selectedPermissions' => 'array',
            'id_card_number' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:20',
            'ssn' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:6';
        } else {
            $rules['password'] = 'nullable|min:6';
        }

        $validated = $this->validate($rules);
        
        $validated['permissions'] = $this->selectedPermissions;

        if ($this->isEditing) {
            $userService->updateEmployee($this->editingId, $validated);
            Flux::toast(__('Employee updated successfully.'), variant: 'success');
        } else {
            $userService->createEmployee($validated);
            Flux::toast(__('Employee created successfully.'), variant: 'success');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function delete(int $id, UserService $userService)
    {
        $this->authorize(PermissionType::DELETE_USERS->value);
        $userService->deleteEmployee($id);
        Flux::toast(__('Employee deleted.'), variant: 'danger');
    }

    private function resetForm()
    {
        $this->reset([
            'last_name', 'first_name', 'email', 'password', 
            'department_id', 'work_schedule_id', 'employment_type', 'role', 
            'editingId', 'selectedPermissions',
            'id_card_number', 'tax_id', 'ssn', 'address', 'phone'
        ]);
        $this->updateRolePermissions();
    }
}
