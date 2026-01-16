<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentUserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function getSubordinates(int $managerId): Collection
    {
        return User::where('manager_id', $managerId)->get();
    }

    public function getSubordinatesPaginated(int $managerId, int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->model->with(['department', 'roles'])
            ->where('manager_id', $managerId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return User::where('department_id', $departmentId)->get();
    }

    public function getPaginated(int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = $this->model->with(['department', 'roles']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function syncRoles(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    public function getUsersWithoutLeaveBalance(int $year): Collection
    {
        return User::whereDoesntHave('leaveBalances', function ($query) use ($year) {
            $query->where('year', $year)
                  ->where('type', 'vacation');
        })
        ->orderBy('name')
        ->get();
    }
}
