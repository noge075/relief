<?php

namespace App\Repositories\Eloquent;

use App\Models\AttendanceLog;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAttendanceLogRepository extends BaseRepository implements AttendanceLogRepositoryInterface
{
    public function __construct(AttendanceLog $model)
    {
        parent::__construct($model);
    }

    public function getDailyStatuses(string $date, ?int $departmentId = null): Collection
    {
        $query = AttendanceLog::with('user')
        ->where('date', $date);

        if ($departmentId) {
            $query->whereHas('user.departments', function ($q) use ($departmentId) {
                $q->where('departments.id', $departmentId);
            });
        }

        return $query->get();
    }

    public function getLogsForPeriod(string $startDate, string $endDate, ?int $userId = null): Collection
    {
        $query = AttendanceLog::whereBetween('date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('date')->get();
    }

    public function updateOrCreateLog(int $userId, string $date, string $status, ?float $hours = null): AttendanceLog
    {
        return AttendanceLog::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            ['status' => $status, 'worked_hours' => $hours]
        );
    }
}
