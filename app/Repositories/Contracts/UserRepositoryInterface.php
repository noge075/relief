<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    // Visszaadja a vezető közvetlen beosztottait
    public function getSubordinates(int $managerId): Collection;

    // HR-nek: csoport szerinti szűrés
    public function getByDepartment(int $departmentId): Collection;
}