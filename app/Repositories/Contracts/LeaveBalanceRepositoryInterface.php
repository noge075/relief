<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveBalance;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveBalanceRepositoryInterface extends BaseRepositoryInterface
{
    public function getBalance(int $userId, int $year, string $type): ?LeaveBalance;
    public function hasBalance(int $userId, int $year, string $type): bool;
    public function incrementUsed(int $userId, int $year, string $type, float $days): void;
    public function decrementUsed(int $userId, int $year, string $type, float $days): void;
    
    public function getPaginated(int $year, array $filters = [], int $perPage = 10, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
    public function getPaginatedForManager(int $managerId, int $year, array $filters = [], int $perPage = 10, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
}
