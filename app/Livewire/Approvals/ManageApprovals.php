<?php

namespace App\Livewire\Approvals;

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
        $this->authorize('approve leave requests');
    }

    public function approve($id)
    {
        $this->authorize('approve leave requests');
        
        try {
            $this->leaveRequestService->approveRequest($id, auth()->user());
            Flux::toast(__('Request approved successfully.'), variant: 'success');
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
    }

    public function openRejectModal($id)
    {
        $this->authorize('approve leave requests');
        $this->rejectingId = $id;
        $this->managerComment = '';
        $this->showRejectModal = true;
    }

    public function reject()
    {
        $this->authorize('approve leave requests');
        
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
        // Ha HR, akkor mindenkit láthat? Vagy csak a sajátjait?
        // A specifikáció szerint: "Közvetlen vezető a saját csapatát".
        // De a HR általában mindent.
        // Nézzük meg a jogosultságot. Ha 'manage settings' (HR) joga van, akkor mindent?
        // Vagy csináljunk egy kapcsolót?
        // Egyszerűsítsünk: A repository getPendingForManager metódusa a manager_id alapján szűr.
        // Ha HR, akkor használjunk egy getAllPending metódust (amit létre kell hozni).
        
        $user = auth()->user();
        
        if ($user->hasRole('hr') || $user->hasRole('super-admin')) {
             // HR lát mindent (ehhez kell új repo metódus)
             // De a getPendingForManager csak managerre szűr.
             // Használjuk a sima get-et státuszra szűrve, de paginálva.
             // A repository-ban nincs getAllPendingPaginated.
             // Hozzuk létre a repository-ban a getPendingRequests($managerId = null) metódust.
             $requests = $this->leaveRequestRepository->getPendingRequests(null); // Mindenki
        } else {
             $requests = $this->leaveRequestRepository->getPendingRequests($user->id);
        }

        return view('livewire.approvals.manage-approvals', [
            'requests' => $requests
        ])->title(__('Approvals'));
    }
}
