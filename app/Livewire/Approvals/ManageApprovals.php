<?php

namespace App\Livewire\Approvals;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use App\Enums\PermissionType;
use App\Livewire\Traits\WithSorting;
use App\Repositories\Contracts\LeaveRequestRepositoryInterface;
use App\Services\LeaveRequestService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Lazy;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

#[Lazy]
class ManageApprovals extends Component
{
    use WithPagination;
    use WithSorting;
    use AuthorizesRequests;

    public $search = '';
    public $typeFilter = '';
    public $perPage = 10;
    public $showRejectModal = false;
    public $rejectingId = null;
    public $managerComment = '';

    protected LeaveRequestRepositoryInterface $leaveRequestRepository;
    protected LeaveRequestService $leaveRequestService;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'perPage' => ['except' => 10, 'as' => 'per_page'],
        'sortCol' => ['except' => 'start_date'],
        'sortAsc' => ['except' => true],
    ];

    public function boot(
        LeaveRequestRepositoryInterface $leaveRequestRepository,
        LeaveRequestService             $leaveRequestService
    )
    {
        $this->leaveRequestRepository = $leaveRequestRepository;
        $this->leaveRequestService = $leaveRequestService;
    }

    public function mount(): void
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        $this->sortCol = 'start_date';

        $this->perPage = request()->query('per_page', 10);
    }

    public function placeholder()
    {
        return view('livewire.placeholders.manage-approvals');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->perPage = 10;
        $this->resetPage();
    }

    public function approve($id): void
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);

        try {
            $this->leaveRequestService->approveRequest($id, auth()->user());
            Flux::toast(__('Request approved successfully.'), variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function openRejectModal($id): void
    {
        $this->authorize(PermissionType::APPROVE_LEAVE_REQUESTS->value);
        $this->rejectingId = $id;
        $this->managerComment = '';
        $this->showRejectModal = true;
    }

    public function reject(): void
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
        $filters = [
            'search' => $this->search,
            'type' => $this->typeFilter,
        ];

        $requests = $this->leaveRequestRepository->getPendingRequests(
            auth()->user()->can('view all leave requests') ? null : auth()->id(),
            (int)$this->perPage,
            $filters,
            $this->sortCol,
            $this->sortAsc
        );

        $requests->appends([
            'search' => $this->search,
            'typeFilter' => $this->typeFilter,
            'per_page' => $this->perPage,
            'sortCol' => $this->sortCol,
            'sortAsc' => $this->sortAsc,
        ]);

        return view('livewire.approvals.manage-approvals', [
            'requests' => $requests
        ])->title(__('Approvals'));
    }
}
