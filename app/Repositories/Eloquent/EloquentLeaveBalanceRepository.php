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

    public function getPaginated(int $year, array $filters = [], int $perPage = 10, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = LeaveBalance::with('user')
            ->where('year', $year);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if ($sortCol === 'name') {
            $query->join('users', 'leave_balances.user_id', '=', 'users.id')
                ->orderBy('users.last_name', $sortAsc ? 'asc' : 'desc')
                ->orderBy('users.first_name', $sortAsc ? 'asc' : 'desc')
                ->select('leave_balances.*');
        } else {
            $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getPaginatedForManager(int $managerId, int $year, array $filters = [], int $perPage = 10, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = LeaveBalance::with('user')
            ->where('year', $year)
            ->whereHas('user', function ($q) use ($managerId) {
                $q->where('manager_id', $managerId);
            });

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                          ->orWhere('last_name', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        if ($sortCol === 'name') {
            $query->join('users', 'leave_balances.user_id', '=', 'users.id')
                ->orderBy('users.last_name', $sortAsc ? 'asc' : 'desc')
                ->orderBy('users.first_name', $sortAsc ? 'asc' : 'desc')
                ->select('leave_balances.*');
        } else {
            $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }
}
