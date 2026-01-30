<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\Department;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Lazy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class ManageDepartments extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    #[Url]
    public $search = '';
    #[Url]
    public $perPage = 10;
    
    // Form
    public $showModal = false;
    public $editingId = null;
    public $name = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'name'],
        'sortAsc' => ['except' => true],
    ];

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_DEPARTMENTS->value);
        $this->sortCol = 'name';
        $this->perPage = request()->query('per_page', 10);
    }

    public function placeholder()
    {
        return view('livewire.placeholders.manage-departments');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function create()
    {
        $this->authorize(PermissionType::MANAGE_DEPARTMENTS->value);
        $this->reset(['editingId', 'name']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::MANAGE_DEPARTMENTS->value);
        $department = Department::find($id);
        if ($department) {
            $this->editingId = $department->id;
            $this->name = $department->name;
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_DEPARTMENTS->value);
        
        $this->validate([
            'name' => 'required|string|min:2|max:255|unique:departments,name,' . ($this->editingId ?? 'NULL'),
        ]);

        if ($this->editingId) {
            Department::find($this->editingId)->update(['name' => $this->name]);
            Flux::toast(__('Department updated successfully.'), variant: 'success');
        } else {
            Department::create(['name' => $this->name]);
            Flux::toast(__('Department created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->authorize(PermissionType::MANAGE_DEPARTMENTS->value);
        
        $department = Department::find($id);
        
        if ($department->users()->exists()) {
            Flux::toast(__('Cannot delete department because it has employees assigned.'), variant: 'danger');
            return;
        }
        
        $department->delete();
        Flux::toast(__('Department deleted.'), variant: 'success');
    }

    public function render()
    {
        $query = Department::query()->withCount('users');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
        
        $departments = $query->paginate((int) $this->perPage);
        
        $departments->appends([
            'search' => $this->search,
            'per_page' => $this->perPage,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.settings.manage-departments', [
            'departments' => $departments
        ])->title(__('Departments'));
    }
}
