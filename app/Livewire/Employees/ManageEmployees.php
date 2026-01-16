<?php

namespace App\Livewire\Employees;

use App\Repositories\Contracts\UserRepositoryInterface;
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
    use AuthorizesRequests;

    public $showModal = false;
    public $isEditing = false;
    public $search = '';
    public $editingId = null;

    public $name = '';
    public $email = '';
    public $password = '';
    public $department_id = null;
    public $work_schedule_id = null;
    public $role = 'employee';

    public function mount()
    {
        $this->authorize('view users');
    }

    public function render(UserService $userService)
    {
        return view('livewire.employees.manage-employees', [
            'users' => $userService->getEmployeesList(auth()->user(), 10, $this->search),
            'departments' => Department::all(),
            'schedules' => WorkSchedule::all(),
            'roles' => Role::all(),
        ])->title(__('Employees'));
    }

    public function openCreate()
    {
        $this->authorize('create users');
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function openEdit(int $id, UserRepositoryInterface $userRepository)
    {
        $this->authorize('edit users');
        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $id;

        $user = $userRepository->find($id);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->department_id = $user->department_id;
        $this->work_schedule_id = $user->work_schedule_id;
        $this->role = $user->roles->first()?->name ?? 'employee';

        $this->showModal = true;
    }

    public function save(UserService $userService)
    {
        if ($this->isEditing) {
            $this->authorize('edit users');
        } else {
            $this->authorize('create users');
        }

        $rules = [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email,' . ($this->editingId ?? 'NULL'),
            'department_id' => 'nullable|exists:departments,id',
            'work_schedule_id' => 'nullable|exists:work_schedules,id',
            'role' => 'required|exists:roles,name',
        ];

        if (!$this->isEditing) {
            $rules['password'] = 'required|min:6';
        } else {
            $rules['password'] = 'nullable|min:6';
        }

        $validated = $this->validate($rules);

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
        $this->authorize('delete users');
        $userService->deleteEmployee($id);
        Flux::toast(__('Employee deleted.'), variant: 'danger');
    }

    private function resetForm()
    {
        $this->reset(['name', 'email', 'password', 'department_id', 'work_schedule_id', 'role', 'editingId']);
    }
}
