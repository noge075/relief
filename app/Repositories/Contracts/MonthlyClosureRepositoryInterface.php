<?php

namespace App\Repositories\Contracts;

use App\Models\MonthlyClosure;

interface MonthlyClosureRepositoryInterface extends BaseRepositoryInterface
{
    // Igaz, ha az adott hónap le van zárva
    // A date paraméter lehet bármilyen nap a hónapban, a repó majd kezeli az elsejét
    public function isMonthClosed(string $date): bool;

    // Hónap lezárása (HR funkció)
    public function closeMonth(string $yearMonth, int $closedByUserId): MonthlyClosure;

    // Hónap újranyitása (Kivételes eset)
    public function reopenMonth(string $yearMonth): bool;
}