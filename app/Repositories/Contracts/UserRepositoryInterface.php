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
    public function getSubordinatesPaginated(int $managerId, int $perPage = 10, ?string $search = null): LengthAwarePaginator;

    // HR-nek: csoport szerinti szűrés
    public function getByDepartment(int $departmentId): Collection;

    public function getPaginated(int $perPage = 10, ?string $search = null): LengthAwarePaginator;
    public function syncRoles(User $user, array $roles);
    
    // Visszaadja azokat a felhasználókat, akiknek nincs szabadságkerete az adott évre
    public function getUsersWithoutLeaveBalance(int $year): Collection;
}
