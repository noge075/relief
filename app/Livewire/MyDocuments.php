<?php

namespace App\Livewire;

use App\Models\AttendanceDocument;
use App\Models\User;
use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;
use Flux\Flux;
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

    protected AttendanceDocumentRepositoryInterface $attendanceDocumentRepository;

    public function boot(AttendanceDocumentRepositoryInterface $attendanceDocumentRepository)
    {
        $this->attendanceDocumentRepository = $attendanceDocumentRepository;
    }

    public function save()
    {
        $this->validate([
            'upload' => 'required|file|max:10240', // 10MB
        ]);

        auth()->user()
            ->addMedia($this->upload)
            ->toMediaCollection($this->collection);

        Flux::toast(__('Document uploaded successfully.'), variant: 'success');
        $this->reset('upload');
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
        $isOwnPersonalDoc = $media->model_type === User::class && $media->model_id === auth()->id();

        $isOwnAttendanceDoc = $media->model_type === AttendanceDocument::class &&
            $this->attendanceDocumentRepository->belongsToUser($media->model_id, $userId);

        if ($isOwnPersonalDoc || $isOwnAttendanceDoc) {
            return response()->download($media->getPath(), $media->file_name);
        }

        abort(403);
    }

    public function render()
    {
        $documents = $this->attendanceDocumentRepository->getAllMediaForUser(auth()->id());

        return view('livewire.my-documents', [
            'documents' => $documents
        ])->title(__('My Documents'));
    }
}
