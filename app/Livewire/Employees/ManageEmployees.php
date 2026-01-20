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
    
    // Filters
    public $departmentFilter = null;
    public $roleFilter = null;
    public $statusFilter = null;
    public $employmentTypeFilter = null;
    public $workScheduleFilter = null;

    public $editingId = null;

    // Form
    public $name = '';
    public $email = '';
    public $password = '';
    public $department_id = null;
    public $work_schedule_id = null;
    public $employment_type = null;
    public $role;
    public $selectedPermissions = [];
    public $rolePermissions = [];
    
    public $allPermissions;
    
    // Colors
    public $deptColors = ['indigo', 'fuchsia', 'teal', 'rose', 'cyan', 'amber', 'violet', 'lime', 'sky', 'pink'];

    public function mount(RoleService $roleService)
    {
        $this->authorize(PermissionType::VIEW_USERS->value);
        $this->sortCol = 'name';
        $this->allPermissions = $roleService->getGroupedPermissions();
        $this->updateRolePermissions();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedDepartmentFilter() { $this->resetPage(); }
    public function updatedRoleFilter() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedEmploymentTypeFilter() { $this->resetPage(); }
    public function updatedWorkScheduleFilter() { $this->resetPage(); }
    
    public function updatedRole()
    {
        $this->updateRolePermissions();
    }

    public function updateRolePermissions()
    {
        if ($this->role) {
            $roleModel = Role::where('name', $this->role)->first();
            $this->rolePermissions = $roleModel ? $roleModel->permissions->pluck('name')->toArray() : [];
        } else {
            $this->rolePermissions = [];
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
        $this->resetPage();
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

        return view('livewire.employees.manage-employees', [
            'users' => $userService->getEmployeesList(auth()->user(), 10, $filters, $this->sortCol, $this->sortAsc),
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
        
        // Alapértelmezett értékek beállítása
        $this->department_id = Department::first()?->id;
        $this->work_schedule_id = WorkSchedule::first()?->id;
        $this->employment_type = EmploymentType::STANDARD->value;
        $this->role = RoleType::EMPLOYEE->value;
        $this->updateRolePermissions();

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

        $this->name = $user->name;
        $this->email = $user->email;
        $this->department_id = $user->department_id;
        $this->work_schedule_id = $user->work_schedule_id;
        $this->employment_type = $user->employment_type?->value;
        $this->role = $user->roles->first()?->name ?? null;
        
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
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            'department_id' => 'nullable|exists:departments,id',
            'work_schedule_id' => 'nullable|exists:work_schedules,id',
            'employment_type' => 'required',
            'role' => 'required|exists:roles,name',
            'selectedPermissions' => 'array',
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
        $this->reset(['name', 'email', 'password', 'department_id', 'work_schedule_id', 'employment_type', 'role', 'editingId', 'selectedPermissions']);
        $this->updateRolePermissions();
    }
}
