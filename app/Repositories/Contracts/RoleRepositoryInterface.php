<?php

namespace App\Repositories\Contracts;

use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    public function syncPermissions(Role $role, array $permissions): Role;
    public function getAllPermissions(): Collection;
    public function getPaginated(int $perPage = 10, ?string $search = null, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator;
}
