<?php

namespace App\Livewire\Settings;

use App\Enums\PermissionType;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Lazy;

#[Lazy]
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

    public function placeholder()
    {
        return view('livewire.placeholders.manage-audit-logs');
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedEventFilter()
    {
        $this->resetPage();
    }

    public function updatedCauserFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

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

    public function getSubjectDescription(Activity $activity): string
    {
        if (!$activity->subject) {
            return __('Unknown');
        }

        $type = class_basename($activity->subject_type);
        $id = $activity->subject_id;

        return __($type) . ' #' . $id;
    }

    public function getCauserName(Activity $activity): string
    {
        if (!$activity->causer) {
            return __('System');
        }

        return $activity->causer->name;
    }

    public function getSubjectUserName(Activity $activity): string
    {
        $subject = $activity->subject;

        if (!$subject) {
            return __('Unknown');
        }

        if ($subject->user) {
            return $subject->user->name;
        }

        return __('Unknown');
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
                $q->where('first_name', 'like', '%' . $this->causerFilter . '%')
                    ->orWhere('last_name', 'like', '%' . $this->causerFilter . '%');
            });
        }

        $activities = $query->paginate((int)$this->perPage);

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
