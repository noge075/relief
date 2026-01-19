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

    public function getSubordinatesPaginated(int $managerId, int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = $this->model->with(['department', 'roles'])
            ->where('manager_id', $managerId);

        $this->applyFilters($query, $filters);

        return $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc')->paginate($perPage);
    }

    public function getByDepartment(int $departmentId): Collection
    {
        return User::where('department_id', $departmentId)->get();
    }

    public function getPaginated(int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = $this->model->with(['department', 'roles']);

        $this->applyFilters($query, $filters);

        return $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc')->paginate($perPage);
    }

    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status']);
        }

        if (!empty($filters['role'])) {
            $query->role($filters['role']);
        }
        
        if (!empty($filters['employment_type'])) {
            $query->where('employment_type', $filters['employment_type']);
        }

        if (!empty($filters['work_schedule_id'])) {
            $query->where('work_schedule_id', $filters['work_schedule_id']);
        }
    }

    public function syncRoles(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    public function syncPermissions(User $user, array $permissions): void
    {
        $user->syncPermissions($permissions);
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
