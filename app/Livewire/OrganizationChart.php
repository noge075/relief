<?php

namespace App\Livewire;

use App\Enums\PermissionType;
use App\Models\Department;
use App\Models\User;
use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\Lazy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class OrganizationChart extends Component
{
    use AuthorizesRequests;

    public $tree = [];
    public $usersWithoutManager = [];
    
    // Szerkesztés
    public $showEditModal = false;
    public $selectedUserId = null;
    public $selectedManagerId = null;

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_USERS->value);
        $this->loadTree();
    }

    public function placeholder()
    {
        return view('livewire.placeholders.organization-chart');
    }

    public function loadTree()
    {
        $users = User::with('subordinates')->get();
        $this->tree = $users->whereNull('manager_id')->values();
    }
    
    public function openEdit($userId)
    {
        $this->authorize(PermissionType::EDIT_USERS->value);
        $this->selectedUserId = $userId;
        $user = User::find($userId);
        $this->selectedManagerId = $user->manager_id;
        $this->showEditModal = true;
    }
    
    public function saveManager()
    {
        $this->authorize(PermissionType::EDIT_USERS->value);
        
        if ($this->selectedUserId == $this->selectedManagerId) {
            Flux::toast(__('Cannot report to self.'), variant: 'danger');
            return;
        }
        
        $user = User::find($this->selectedUserId);
        $user->manager_id = $this->selectedManagerId;
        $user->save();
        
        $this->loadTree();
        $this->showEditModal = false;
        Flux::toast(__('Manager updated successfully.'), variant: 'success');
    }
    
    public function updateManager($userId, $newManagerId)
    {
        $this->authorize(PermissionType::EDIT_USERS->value);

        if ($userId == $newManagerId) {
            Flux::toast(__('Cannot report to self.'), variant: 'danger');
            $this->loadTree();
            return;
        }
        
        if ($newManagerId === 'root' || $newManagerId === '') {
            $newManagerId = null;
        }

        $user = User::find($userId);
        $user->manager_id = $newManagerId;
        $user->save();

        $this->loadTree();
        Flux::toast(__('Hierarchy updated.'), variant: 'success');
    }

    public function render()
    {
        return view('livewire.organization-chart', [
            'allUsers' => User::orderBy('last_name')->get(),
            'departments' => Department::all()
        ])->title(__('Organization Chart'));
    }
}
