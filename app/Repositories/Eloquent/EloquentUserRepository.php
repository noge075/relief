<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
        $query = $this->model->with(['departments', 'roles', 'homeOfficePolicy'])
            ->where('manager_id', $managerId);

        $this->applyFilters($query, $filters);

        if ($sortCol === 'name') {
            $query->orderBy('last_name', $sortAsc ? 'asc' : 'desc')
                  ->orderBy('first_name', $sortAsc ? 'asc' : 'desc');
        } else {
            $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getPaginated(int $perPage = 10, array $filters = [], string $sortCol = 'name', bool $sortAsc = true): LengthAwarePaginator
    {
        $query = $this->model->with(['departments', 'roles', 'homeOfficePolicy']);

        $this->applyFilters($query, $filters);

        if ($sortCol === 'name') {
            $query->orderBy('last_name', $sortAsc ? 'asc' : 'desc')
                  ->orderBy('first_name', $sortAsc ? 'asc' : 'desc');
        } else {
            $query->orderBy($sortCol, $sortAsc ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    protected function applyFilters($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('last_name', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['department_id'])) {
            $query->whereHas('departments', function ($q) use ($filters) {
                $q->where('departments.id', $filters['department_id']);
            });
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

        if (!empty($filters['home_office_policy_id'])) {
            $query->where('home_office_policy_id', $filters['home_office_policy_id']);
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
        ->orderBy('last_name')
        ->get();
    }

    public function getAllActiveOrderedByName(): Collection
    {
        return $this->model->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function getByIds(array $ids): Collection
    {
        return $this->model->whereIn('id', $ids)->get();
    }
}
