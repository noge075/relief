<?php

namespace App\Livewire;

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

        if ($media && $media->model_id === auth()->id() && $media->model_type === \App\Models\User::class) {
            $media->delete();
            Flux::toast(__('Document deleted.'), variant: 'success');
        }
    }

    public function download($id)
    {
        $media = Media::find($id);

        if ($media && $media->model_id === auth()->id() && $media->model_type === \App\Models\User::class) {
            return response()->download($media->getPath(), $media->file_name);
        }
    }

    public function render()
    {
        $documents = auth()->user()->getMedia($this->collection);

        return view('livewire.my-documents', [
            'documents' => $documents
        ])->title(__('My Documents'));
    }
}
