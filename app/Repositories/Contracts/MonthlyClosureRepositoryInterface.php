<?php

namespace App\Repositories\Contracts;

use App\Models\MonthlyClosure;

interface MonthlyClosureRepositoryInterface extends BaseRepositoryInterface
{
    public function isMonthClosed(string $date): bool;
    public function closeMonth(string $yearMonth, int $closedByUserId): MonthlyClosure;
    public function reopenMonth(string $yearMonth): bool;
}