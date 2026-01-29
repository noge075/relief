<?php

namespace App\Livewire\Roles;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Services\RoleService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageRoles extends Component
{
    use WithPagination;
    use WithSorting;
    use AuthorizesRequests;

    public $search = '';
    public $perPage = 10;

    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form
    public $name = '';
    public $selectedPermissions = [];
    public $permissions = [];

    protected RoleService $roleService;

    protected $rules = [
        'name' => 'required|string|min:3|unique:roles,name',
        'selectedPermissions' => 'array',
    ];

    public function boot(RoleService $roleService): void
    {
        $this->roleService = $roleService;
    }

    public function mount(): void
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->sortCol = 'name';
        $this->permissions = $this->roleService->getGroupedPermissions();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id): void
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['name', 'selectedPermissions']);
        $this->isEditing = true;
        $this->editingId = $id;

        $role = $this->roleService->getRole($id);
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);

        if ($this->isEditing) {
            $this->rules['name'] = 'required|string|min:3|unique:roles,name,' . $this->editingId;
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'permissions' => $this->selectedPermissions,
        ];

        if ($this->isEditing) {
            $this->roleService->updateRole($this->editingId, $data);
            Flux::toast(__('Role updated successfully.'), variant: 'success');
        } else {
            $this->roleService->createRole($data);
            Flux::toast(__('Role created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id): void
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->roleService->deleteRole($id);
        Flux::toast(__('Role deleted.'), variant: 'danger');
    }

    public function render()
    {
        $roles = $this->roleService->getPaginatedRoles(
            $this->perPage,
            $this->search,
            $this->sortCol,
            $this->sortAsc
        );

        return view('livewire.roles.manage-roles', [
            'roles' => $roles,
        ])->title(__('Roles & Permissions'));
    }
}
