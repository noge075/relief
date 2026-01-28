<?php
namespace App\Repositories\Eloquent;

use App\Models\AttendanceDocument;
use App\Repositories\Contracts\AttendanceDocumentRepositoryInterface;

class EloquentAttendanceDocumentRepository extends BaseRepository implements AttendanceDocumentRepositoryInterface
{
    public function __construct(AttendanceDocument $model)
    {
        parent::__construct($model);
    }
}