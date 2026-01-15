<?php

namespace App\Repositories\Eloquent;

use App\Models\AttendanceLog;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentAttendanceLogRepository extends BaseRepository implements AttendanceLogRepositoryInterface
{
    public function getDailyStatuses(string $date, ?int $departmentId = null): Collection
    {
        $query = AttendanceLog::with('user')
        ->where('date', $date);

        if ($departmentId) {
            // Itt használjuk a kapcsolatot: logs -> user -> department
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
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