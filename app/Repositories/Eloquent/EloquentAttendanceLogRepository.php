<?php

namespace App\Repositories\Eloquent;

use App\Enums\AttendanceStatusType;
use App\Models\AttendanceLog;
use App\Repositories\Contracts\AttendanceLogRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EloquentAttendanceLogRepository extends BaseRepository implements AttendanceLogRepositoryInterface
{
    public function __construct(AttendanceLog $model)
    {
        parent::__construct($model);
    }

    public function getDailyStatuses(string $date, ?int $departmentId = null): Collection
    {
        $query = $this->model::with('user')
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
        $query = $this->model::whereBetween('date', [$startDate, $endDate]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('date')->get();
    }

    public function updateOrCreateLog(
        int $userId,
        string $date,
        AttendanceStatusType $status,
        ?float $hours = null,
        ?Carbon $checkIn = null,
        ?Carbon $checkOut = null
    ): AttendanceLog
    {
        return $this->model::updateOrCreate(
            ['user_id' => $userId, 'date' => $date],
            [
                'status' => $status,
                'worked_hours' => $hours,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ]
        );
    }
}
