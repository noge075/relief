<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageAuditLogs extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $search = '';
    public $eventFilter = '';
    public $causerFilter = '';
    public $perPage = 10;
    
    // Modal
    public $showDetailsModal = false;
    public $selectedActivity = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'eventFilter' => ['except' => ''],
        'causerFilter' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'created_at'],
        'sortAsc' => ['except' => false],
    ];

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_AUDIT_LOGS->value);
        
        $this->perPage = request()->query('per_page', 10);
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedEventFilter() { $this->resetPage(); }
    public function updatedCauserFilter() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->eventFilter = '';
        $this->causerFilter = '';
        $this->perPage = 10;
        $this->resetPage();
    }
    
    public function showDetails($id)
    {
        $this->selectedActivity = Activity::with('causer', 'subject')->find($id);
        $this->showDetailsModal = true;
    }

    public function render()
    {
        $query = Activity::with('causer', 'subject')->latest();

        if ($this->search) {
            $query->where('description', 'like', '%' . $this->search . '%')
                  ->orWhere('properties', 'like', '%' . $this->search . '%');
        }

        if ($this->eventFilter) {
            $query->where('event', $this->eventFilter);
        }

        if ($this->causerFilter) {
            $query->whereHas('causer', function ($q) {
                $q->where('name', 'like', '%' . $this->causerFilter . '%');
            });
        }
        
        $activities = $query->paginate((int) $this->perPage);
        
        $activities->appends([
            'search' => $this->search,
            'eventFilter' => $this->eventFilter,
            'causerFilter' => $this->causerFilter,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.settings.manage-audit-logs', [
            'activities' => $activities
        ])->title(__('Audit Logs'));
    }
}
