<?php

namespace App\Repositories\Contracts;

use App\Models\AttendanceLog;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceLogRepositoryInterface extends BaseRepositoryInterface
{
    // A napi státuszfalhoz: Ki hol van egy adott napon?
    // Opcionálisan szűrhető részlegre (Department)
    public function getDailyStatuses(string $date, ?int $departmentId = null): Collection;

    // A havi exporthoz: Egy adott időszak összes adata
    public function getLogsForPeriod(string $startDate, string $endDate, ?int $userId = null): Collection;

    // Tényadat rögzítése/frissítése (pl. amikor egy request approved lesz)
    public function updateOrCreateLog(int $userId, string $date, string $status, ?float $hours = null): AttendanceLog;
}