<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyDirectory extends Component
{
    use WithPagination;

    public $search = '';
    public $departmentFilter = '';
    public $perPage = 12;

    protected $queryString = [
        'search' => ['except' => ''],
        'departmentFilter' => ['except' => ''],
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDepartmentFilter()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'departmentFilter']);
        $this->resetPage();
    }

    public function render()
    {
        $users = User::where('is_active', true)
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($this->departmentFilter, function ($query, $departmentId) {
                $query->where('department_id', $departmentId);
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($this->perPage);

        $departments = Department::orderBy('name')->get();

        return view('livewire.company-directory', [
            'users' => $users,
            'departments' => $departments,
        ])->title(__('Company Directory'));
    }
}
