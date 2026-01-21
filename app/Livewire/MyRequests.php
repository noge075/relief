<?php

namespace App\Livewire;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyRequests extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public $statusFilter = '';
    public $typeFilter = '';
    public $yearFilter = null;
    public $perPage = 10;

    protected $queryString = [
        'statusFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'yearFilter' => ['except' => null],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
    ];

    public function mount()
    {
        $this->perPage = request()->query('per_page', 10);
    }

    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedTypeFilter() { $this->resetPage(); }
    public function updatedYearFilter() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->yearFilter = null;
        $this->perPage = 10;
        $this->resetPage();
    }

    public function render()
    {
        $query = LeaveRequest::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc');

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }
        
        if ($this->yearFilter) {
            $query->whereYear('start_date', $this->yearFilter);
        }
        
        $requests = $query->paginate((int) $this->perPage);
        
        $requests->appends([
            'statusFilter' => $this->statusFilter,
            'typeFilter' => $this->typeFilter,
            'yearFilter' => $this->yearFilter,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.my-requests', [
            'requests' => $requests
        ])->title(__('My Requests'));
    }
}
