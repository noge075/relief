<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

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

    public function getByDepartment(int $departmentId): Collection
    {
        return User::where('department_id', $departmentId)->get();
    }
}