<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    public function getForUser(int $userId, ?string $status = null): Collection;
    public function getForUserInPeriod(int $userId, string $start, string $end): Collection;
    public function getPendingForManager(int $managerId): Collection;
    public function getPendingRequests(?int $managerId = null, int $perPage = 10, array $filters = [], string $sortCol = 'start_date', bool $sortAsc = true): LengthAwarePaginator;

    public function findOverlapping(int $userId, string $start, string $end, ?int $excludeId = null): Collection;
    public function updateStatus(LeaveRequest $request, string $status, ?string $comment = null): bool;
}
