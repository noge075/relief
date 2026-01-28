<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    // Visszaadja a vezető közvetlen beosztottait
    public function getSubordinates(int $managerId): Collection;
    
    // Visszaadja a vezető közvetlen beosztottait paginálva és kereshetően
    public function getSubordinatesPaginated(int $managerId, int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;

    public function getPaginated(int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
    public function syncRoles(User $user, array $roles);
    public function syncPermissions(User $user, array $permissions);
    
    // Visszaadja azokat a felhasználókat, akiknek nincs szabadságkerete az adott évre
    public function getUsersWithoutLeaveBalance(int $year): Collection;

    // Visszaadja az összes aktív felhasználót, név szerint rendezve
    public function getAllActiveOrderedByName(): Collection;

    // Visszaadja a felhasználókat ID-k alapján
    public function getByIds(array $ids): Collection;
}
