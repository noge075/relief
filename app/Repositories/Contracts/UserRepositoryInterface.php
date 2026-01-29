<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function getSubordinates(int $managerId): Collection;
    public function getSubordinatesPaginated(int $managerId, int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
    public function getPaginated(int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
    public function syncRoles(User $user, array $roles);
    public function syncPermissions(User $user, array $permissions);
    public function getUsersWithoutLeaveBalance(int $year): Collection;
    public function getAllActiveOrderedByName(): Collection;
    public function getByIds(array $ids): Collection;
}
