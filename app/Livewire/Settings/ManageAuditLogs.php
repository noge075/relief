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
    
    // Modal
    public $showDetailsModal = false;
    public $selectedActivity = null;

    public function mount()
    {
        $this->authorize(PermissionType::VIEW_AUDIT_LOGS->value);
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedEventFilter() { $this->resetPage(); }
    public function updatedCauserFilter() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->eventFilter = '';
        $this->causerFilter = '';
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

        return view('livewire.settings.manage-audit-logs', [
            'activities' => $query->paginate(20)
        ])->title(__('Audit Logs'));
    }
}
