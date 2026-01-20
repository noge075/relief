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
    public $yearFilter = null; // Alapból null (minden év)

    public function mount()
    {
        // $this->yearFilter = Carbon::now()->year; // Kivettem, hogy mindent mutasson alapból
    }

    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedTypeFilter() { $this->resetPage(); }
    public function updatedYearFilter() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->yearFilter = null;
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

        return view('livewire.my-requests', [
            'requests' => $query->paginate(10)
        ])->title(__('My Requests'));
    }
}
