<?php

namespace App\Livewire;

use App\Models\LeaveRequest;
use App\Enums\LeaveStatus;
use App\Services\LeaveRequestService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Url;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MyRequests extends Component
{
    use AuthorizesRequests;
    use WithPagination;
    use WithFileUploads;

    #[Url]
    public $statusFilter = '';
    #[Url]
    public $typeFilter = '';
    #[Url]
    public $yearFilter = null;
    #[Url]
    public $perPage = 10;
    
    // Modal
    public $showDetailsModal = false;
    public $selectedRequest = null;
    public $upload;

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
    
    public function openDetails($id)
    {
        $this->selectedRequest = LeaveRequest::with('media')->find($id);
        
        if (!$this->selectedRequest || $this->selectedRequest->user_id !== auth()->id()) {
            return;
        }
        
        $this->reset('upload');
        $this->showDetailsModal = true;
    }
    
    public function saveDocument()
    {
        $this->validate([
            'upload' => 'required|file|max:10240', // 10MB
        ]);
        
        if ($this->selectedRequest) {
            $this->selectedRequest->addMedia($this->upload)
                ->toMediaCollection('documents');
                
            Flux::toast(__('Document uploaded successfully.'), variant: 'success');
            $this->reset('upload');
            $this->selectedRequest->refresh();
        }
    }
    
    public function deleteDocument($mediaId)
    {
        $media = Media::find($mediaId);
        
        if ($media && $media->model_id === $this->selectedRequest->id) {
            if ($this->selectedRequest->status !== LeaveStatus::PENDING) {
                 Flux::toast(__('Cannot delete documents from processed requests.'), variant: 'danger');
                 return;
            }

            $media->delete();
            Flux::toast(__('Document deleted.'), variant: 'success');
            $this->selectedRequest->refresh();
        }
    }

    public function deleteRequest($id, LeaveRequestService $leaveRequestService)
    {
        try {
            $leaveRequestService->deleteRequest($id, auth()->id());
            Flux::toast(__('Request deleted.'), variant: 'success');
            
            // Ha a modal nyitva van és a törölt elemet nézzük, zárjuk be
            if ($this->showDetailsModal && $this->selectedRequest && $this->selectedRequest->id === $id) {
                $this->showDetailsModal = false;
                $this->selectedRequest = null;
            }
            
        } catch (\Exception $e) {
            Flux::toast($e->getMessage(), variant: 'danger');
        }
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
