<?php

namespace App\Repositories\Contracts;

use App\Enums\AttendanceStatusType;
use App\Models\AttendanceLog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceLogRepositoryInterface extends BaseRepositoryInterface
{
    public function getDailyStatuses(string $date, ?int $departmentId = null): Collection;

    public function getLogsForPeriod(string $startDate, string $endDate, ?int $userId = null): Collection;

    public function updateOrCreateLog(
        int                  $userId,
        string               $date,
        AttendanceStatusType $status,
        ?float               $hours = null,
        ?Carbon              $checkIn = null,
        ?Carbon              $checkOut = null
    ): AttendanceLog;
}
