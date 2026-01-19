<?php

namespace App\Livewire\Roles;

use App\Enums\PermissionType;
use App\Services\RoleService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageRoles extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $permissions;
    
    // Form properties
    public $roleId;
    public $name = '';
    public $selectedPermissions = [];
    
    public $showModal = false;
    public $isEditing = false;

    protected $roleService;

    protected $rules = [
        'name' => 'required|string|min:3|unique:roles,name',
        'selectedPermissions' => 'array'
    ];

    public function boot(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->permissions = $this->roleService->getGroupedPermissions();
    }

    public function create()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->reset(['roleId', 'name', 'selectedPermissions']);
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $role = $this->roleService->getRole($id);
        $this->roleId = $id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        
        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $rules = $this->rules;
        if ($this->isEditing) {
            $rules['name'] = 'required|string|min:3|unique:roles,name,' . $this->roleId;
        }
        
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'permissions' => $this->selectedPermissions
        ];

        if ($this->isEditing) {
            $this->roleService->updateRole($this->roleId, $data);
            Flux::toast(__('Role updated successfully.'), variant: 'success');
        } else {
            $this->roleService->createRole($data);
            Flux::toast(__('Role created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->authorize(PermissionType::MANAGE_SETTINGS->value);
        $this->roleService->deleteRole($id);
        Flux::toast(__('Role deleted.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.roles.manage-roles', [
            'roles' => $this->roleService->getPaginatedRoles(10),
        ])->title(__('Manage Roles'));
    }
}
