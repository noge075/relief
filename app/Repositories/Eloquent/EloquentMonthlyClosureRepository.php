<?php

namespace App\Repositories\Eloquent;

use App\Models\MonthlyClosure;
use App\Repositories\Contracts\MonthlyClosureRepositoryInterface;
use Carbon\Carbon;

class EloquentMonthlyClosureRepository extends BaseRepository implements MonthlyClosureRepositoryInterface
{
    public function __construct(MonthlyClosure $model)
    {
        parent::__construct($model);
    }

    public function isMonthClosed(string $date): bool
    {
        $startOfMonth = Carbon::parse($date)->startOfMonth()->format('Y-m-d');

        return $this->model::where('month', $startOfMonth)
            ->where('is_closed', true)
            ->exists();
    }

    public function closeMonth(string $yearMonth, int $closedByUserId): MonthlyClosure
    {
        $startOfMonth = Carbon::parse($yearMonth)->startOfMonth()->format('Y-m-d');

        return $this->model::updateOrCreate(
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
        $closure = $this->model::where('month', $startOfMonth)->first();

        if ($closure) {
            return $closure->update(['is_closed' => false]);
        }

        return false;
    }
}
