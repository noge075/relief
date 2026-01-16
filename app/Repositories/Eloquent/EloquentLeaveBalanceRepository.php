<?php

namespace App\Repositories\Eloquent;

use App\Models\LeaveBalance;
use App\Repositories\Contracts\LeaveBalanceRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

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

    public function hasBalance(int $userId, int $year, string $type): bool
    {
        return LeaveBalance::where('user_id', $userId)
            ->where('year', $year)
            ->where('type', $type)
            ->exists();
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

    public function getPaginated(int $year, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = LeaveBalance::with('user')
            ->where('year', $year);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getPaginatedForManager(int $managerId, int $year, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = LeaveBalance::with('user')
            ->where('year', $year)
            ->whereHas('user', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }
}
