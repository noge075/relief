<?php

namespace App\Repositories\Eloquent;

use App\Models\LeaveBalance;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;

class EloquentLeaveBalanceRepository extends BaseRepository implements LeaveBalanceRepositoryInterface
{
    public function __construct(LeaveBalance $model)
    {
        parent::__construct($model);
    }

    public function getBalance(int $userId, int $year, string $type): ?LeaveBalance
    {
        return LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->where('type', $type)
            ->first();
    }

    public function incrementUsed(int $userId, int $year, string $type, float $days): void
    {
        LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->where('type', $type)
            ->increment('used', $days);
    }

    public function decrementUsed(int $userId, int $year, string $type, float $days): void
    {
        LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->where('type', $type)
            ->decrement('used', $days);
    }
}
