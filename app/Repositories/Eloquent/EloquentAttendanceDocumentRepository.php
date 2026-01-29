<?php
namespace App\Repositories\Eloquent;

use App\Models\AttendanceDocument;
use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentAttendanceDocumentRepository extends BaseRepository implements AttendanceDocumentRepositoryInterface
{
    public function __construct(AttendanceDocument $model)
    {
        parent::__construct($model);
    }

    public function findExisting(int $userId, string $date): ?AttendanceDocument
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('month', $date)
            ->first();
    }

    public function getAllMediaForUser(int $userId): Collection
    {
        $user = $this->find($userId);

        if (!$user) {
            return collect();
        }

        $personalDocs = $user->getMedia('personal_documents');

        $attendanceDocs = $this->model
            ->with('media')
            ->where('user_id', $userId)
            ->get()
            ->flatMap(function ($doc) {
                return $doc->getMedia('signed_sheets');
            });

        return $personalDocs
            ->merge($attendanceDocs)
            ->sortByDesc('created_at')
            ->values();
    }

    public function belongsToUser(int $documentId, int $userId): bool
    {
        return $this->model
            ->where('id', $documentId)
            ->where('user_id', $userId)
            ->exists();
    }
}