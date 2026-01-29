<?php

namespace App\Repositories\Contracts;

use App\Models\AttendanceDocument;
use Illuminate\Support\Collection;

interface AttendanceDocumentRepositoryInterface extends BaseRepositoryInterface {
    public function findExisting(int $userId, string $date): ?AttendanceDocument;
    public function getAllMediaForUser(int $userId): Collection;
    public function belongsToUser(int $documentId, int $userId): bool;
}