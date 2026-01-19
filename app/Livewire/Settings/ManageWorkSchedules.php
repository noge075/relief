<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\WorkSchedule;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageWorkSchedules extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    public $search = '';
    
    // Form
    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $weeklyPattern = [
        'monday' => 8,
        'tuesday' => 8,
        'wednesday' => 8,
        'thursday' => 8,
        'friday' => 8,
        'saturday' => 0,
        'sunday' => 0,
    ];

    protected $rules = [
        'name' => 'required|string|min:3|max:255',
        'weeklyPattern.monday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.tuesday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.wednesday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.thursday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.friday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.saturday' => 'required|numeric|min:0|max:24',
        'weeklyPattern.sunday' => 'required|numeric|min:0|max:24',
    ];

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        $this->sortCol = 'name';
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->reset(['editingId', 'name']);
        $this->weeklyPattern = [
            'monday' => 8, 'tuesday' => 8, 'wednesday' => 8, 'thursday' => 8, 'friday' => 8, 'saturday' => 0, 'sunday' => 0
        ];
        $this->showModal = true;
    }

    public function edit($id)
    {
        $schedule = WorkSchedule::find($id);
        if ($schedule) {
            $this->editingId = $schedule->id;
            $this->name = $schedule->name;
            $this->weeklyPattern = $schedule->weekly_pattern;
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'weekly_pattern' => $this->weeklyPattern,
        ];

        if ($this->editingId) {
            WorkSchedule::find($this->editingId)->update($data);
            Flux::toast(__('Work schedule updated successfully.'), variant: 'success');
        } else {
            WorkSchedule::create($data);
            Flux::toast(__('Work schedule created successfully.'), variant: 'success');
        }

        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        
        // Ellenőrizzük, hogy használja-e valaki
        $schedule = WorkSchedule::withCount('users')->find($id);
        if ($schedule->users_count > 0) {
            Flux::toast(__('Cannot delete schedule because it is assigned to users.'), variant: 'danger');
            return;
        }

        $schedule->delete();
        Flux::toast(__('Work schedule deleted.'), variant: 'success');
    }

    public function render()
    {
        $query = WorkSchedule::query();

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        $schedules = $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc')->paginate(10);

        return view('livewire.settings.manage-work-schedules', [
            'schedules' => $schedules
        ])->title(__('Work Schedules'));
    }
}
