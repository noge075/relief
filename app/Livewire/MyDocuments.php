<?php

namespace App\Livewire;

use App\Models\AttendanceDocument;
use App\Models\User;
use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;
use Flux\Flux;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MyDocuments extends Component
{
    use WithFileUploads;
    use WithPagination;
    use AuthorizesRequests;

    public $upload;
    public $collection = 'personal_documents';
    public $perPage = 10;

    protected AttendanceDocumentRepositoryInterface $attendanceDocumentRepository;

    public function boot(AttendanceDocumentRepositoryInterface $attendanceDocumentRepository)
    {
        $this->attendanceDocumentRepository = $attendanceDocumentRepository;
    }

    public function save()
    {
        $this->validate([
            'upload' => 'required|file|max:10240',
        ]);

        $originalName = $this->upload->getClientOriginalName();
        $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

        auth()->user()
            ->addMedia($this->upload->getRealPath())
            ->usingName($nameWithoutExtension)
            ->usingFileName($originalName)
            ->toMediaCollection($this->collection);

        Flux::toast(__('Document uploaded successfully.'), variant: 'success');

        $this->reset('upload');
        $this->dispatch('file-uploaded');
    }

    public function delete($id)
    {
        $media = Media::find($id);

        if (!$media) return;

        if ($media->model_type === User::class && $media->model_id === auth()->id()) {
            $media->delete();
            Flux::toast(__('Document deleted.'), variant: 'success');
        } else {
            Flux::toast(__('System generated documents cannot be deleted manually.'), variant: 'danger');
        }
    }

    public function download($id)
    {
        $media = Media::find($id);

        if (!$media) abort(404);

        $userId = auth()->id();

        $isOwnPersonalDoc = $media->model_type === User::class && $media->model_id === $userId;

        $isOwnAttendanceDoc = $media->model_type === AttendanceDocument::class &&
            $this->attendanceDocumentRepository->belongsToUser($media->model_id, $userId);

        if ($isOwnPersonalDoc || $isOwnAttendanceDoc) {
            return response()->download($media->getPath(), $media->file_name);
        }

        abort(403);
    }

    public function render()
    {
        $user = auth()->user();

        $personalDocs = $user->getMedia($this->collection);

        $attendanceDocs = $this->attendanceDocumentRepository->getAllMediaForUser($user->id);

        $allMedia = $personalDocs->concat($attendanceDocs)->sortByDesc('created_at');

        $currentPage = Paginator::resolveCurrentPage() ?: 1;

        $currentItems = $allMedia->slice(($currentPage - 1) * $this->perPage, $this->perPage)->all();

        $paginatedDocuments = new LengthAwarePaginator(
            $currentItems,
            $allMedia->count(),
            $this->perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        return view('livewire.my-documents', [
            'documents' => $paginatedDocuments
        ])->title(__('My Documents'));
    }
}