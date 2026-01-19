<?php

namespace App\Livewire\Approvals;

use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Services\LeaveRequestService;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ManageApprovals extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithSorting;

    public $search = '';
    public $typeFilter = null;

    // Reject Modal
    public $showRejectModal = false;
    public $rejectingId = null;
    public $managerComment = '';

    protected LeaveRequestService $leaveRequestService;
    protected LeaveRequestRepositoryInterface $leaveRequestRepository;

    public function boot(
        LeaveRequestService $leaveRequestService,
        LeaveRequestRepositoryInterface $leaveRequestRepository
    ) {
        $this->leaveRequestService = $leaveRequestService;
        $this->leaveRequestRepository = $leaveRequestRepository;
    }

    public function mount()
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        $this->sortCol = 'start_date';
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedTypeFilter() { $this->resetPage(); }

    public function clearFilters()
    {
        $this->search = '';
        $this->typeFilter = null;
        $this->resetPage();
    }

    public function approve($id)
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        
        try {
            $this->leaveRequestService->approveRequest($id, auth()->user());
            Flux::toast(__('Request approved successfully.'), variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function openRejectModal($id)
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        $this->rejectingId = $id;
        $this->managerComment = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        
        $this->validate([
            'managerComment' => 'required|string|max:255',
        ]);

        try {
            $this->leaveRequestService->rejectRequest($this->rejectingId, auth()->user(), $this->managerComment);
            Flux::toast(__('Request rejected.'), variant: 'success');
            $this->showRejectModal = false;
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function render()
    {
        $user = auth()->user();
        
        $filters = [
            'search' => $this->search,
            'type' => $this->typeFilter,
        ];
        
        if ($user->can(PermissionType::VIEW_ALL_LEAVE_REQUESTS->value)) {
             $requests = $this->leaveRequestRepository->getPendingRequests(null, 10, $filters, $this->sortCol, $this->sortAsc);
        } else {
             $requests = $this->leaveRequestRepository->getPendingRequests($user->id, 10, $filters, $this->sortCol, $this->sortAsc);
        }

        return view('livewire.approvals.manage-approvals', [
            'requests' => $requests
        ])->title(__('Approvals'));
    }
}
