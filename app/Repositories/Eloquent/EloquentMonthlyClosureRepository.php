<?php

namespace App\Repositories\Eloquent;

use App\Models\MonthlyClosure;
use App\Repositories\Contracts\MonthlyClosureRepositoryInterface;
use Carbon\Carbon;

class EloquentMonthlyClosureRepository extends BaseRepository implements MonthlyClosureRepositoryInterface
{
    public function isMonthClosed(string $date): bool
    {
        // Átalakítjuk a hónap első napjára, mert úgy tároljuk
        $startOfMonth = Carbon::parse($date)->startOfMonth()->format('Y-m-d');

        return MonthlyClosure::where('month', $startOfMonth)
            ->where('is_closed', true)
            ->exists();
    }

    public function closeMonth(string $yearMonth, int $closedByUserId): MonthlyClosure
    {
        $startOfMonth = Carbon::parse($yearMonth)->startOfMonth()->format('Y-m-d');

        return MonthlyClosure::updateOrCreate(
            ['month' => $startOfMonth],
            [
                'is_closed' => true,
                'closed_by' => $closedByUserId,
                'closed_at' => now(),
            ]
        );
    }

    public function reopenMonth(string $yearMonth): bool
    {
        $startOfMonth = Carbon::parse($yearMonth)->startOfMonth()->format('Y-m-d');

        $closure = MonthlyClosure::where('month', $startOfMonth)->first();

        if ($closure) {
            return $closure->update(['is_closed' => false]);
        }

        return false;
    }
}