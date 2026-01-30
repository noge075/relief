<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Models\WorkSchedule;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Lazy;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class ManageWorkSchedules extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    public $search = '';
    public $perPage = 10;
    
    // Form
    public $showModal = false;
    public $editingId = null;
    public $name = '';
    public $start_time = '08:00';
    public $end_time = '16:30';
    public $weeklyPattern = [
        'monday' => 8,
        'tuesday' => 8,
        'wednesday' => 8,
        'thursday' => 8,
        'friday' => 8,
        'saturday' => 0,
        'sunday' => 0,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'name'],
        'sortAsc' => ['except' => true],
    ];

    public function mount()
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        $this->sortCol = 'name';
        
        $this->perPage = request()->query('per_page', 10);
    }

    public function placeholder()
    {
        return view('livewire.placeholders.manage-work-schedules');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function create()
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        $this->reset(['editingId', 'name', 'start_time', 'end_time']);
        $this->weeklyPattern = [
            'monday' => 8, 'tuesday' => 8, 'wednesday' => 8, 'thursday' => 8, 'friday' => 8, 'saturday' => 0, 'sunday' => 0
        ];
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        $schedule = WorkSchedule::find($id);
        if ($schedule) {
            $this->editingId = $schedule->id;
            $this->name = $schedule->name;
            $this->start_time = $schedule->start_time;
            $this->end_time = $schedule->end_time;
            $this->weeklyPattern = $schedule->weekly_pattern;
            $this->showModal = true;
        }
    }

    public function save()
    {
        $this->authorize(PermissionType::MANAGE_WORK_SCHEDULES->value);
        
        $this->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'weeklyPattern.*' => 'required|numeric|min:0|max:24',
        ]);

        $data = [
            'name' => $this->name,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
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
        
        $schedule = WorkSchedule::find($id);
        if ($schedule->users()->exists()) {
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

        $query->orderBy($this->sortCol, $this->sortAsc ? 'asc' : 'desc');
        
        $schedules = $query->paginate((int) $this->perPage);
        
        $schedules->appends([
            'search' => $this->search,
            'per_page' => $this->perPage,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.settings.manage-work-schedules', [
            'schedules' => $schedules
        ])->title(__('Work Schedules'));
    }
}
