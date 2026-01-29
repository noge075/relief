<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentRoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);
        return $role;
    }

    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    public function getPaginated(int $perPage = 10, ?string $search = null, string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = $this->model->with('permissions')
            ->withCount('permissions');

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc')->paginate($perPage);
    }
}
