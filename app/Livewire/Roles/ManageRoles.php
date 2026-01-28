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
    public $perPage = 10; // Új
    
    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form
    public $name = '';
    public $selectedPermissions = [];
    public $permissions = []; // Grouped permissions for the form

    protected RoleService $roleService;

    protected $rules = [
        'name' => 'required|string|min:3|unique:roles,name',
        'selectedPermissions' => 'array',
    ];

    public function boot(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->sortCol = 'name';
        $this->permissions = $this->roleService->getGroupedPermissions();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); } // Új

    public function create()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['name', 'selectedPermissions']);
        $this->isEditing = true;
        $this->editingId = $id;

        $role = Role::findById($id);
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();

        $this->showModal = true;
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        
        if ($this->isEditing) {
            $this->rules['name'] = 'required|string|min:3|unique:roles,name,' . $this->editingId;
        }

        $this->validate();

        if ($this->isEditing) {
            $role = Role::findById($this->editingId);
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            Flux::toast(__('Role updated successfully.'), variant: 'success');
        } else {
            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            Flux::toast(__('Role created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $role = Role::findById($id);
        $role->delete();
        Flux::toast(__('Role deleted.'), variant: 'danger');
    }

    public function render()
    {
        $query = Role::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $query->withCount('permissions');
        $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');

        return view('livewire.roles.manage-roles', [
            'roles' => $query->paginate($this->perPage) // Új
        ])->title(__('Roles & Permissions'));
    }
}
