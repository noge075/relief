<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;

interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    // Visszaadja egy adott user összes kérelmét (szűrhető státuszra)
    public function getForUser(int $userId, ?string $status = null): Collection;

    // Vezetőnek: a hozzá tartozó jóváhagyásra váró kérelmek
    public function getPendingForManager(int $managerId): Collection;

    // Átfedés vizsgálat: van-e már kérelme az adott időszakra?
    // Fontos: a saját magát (excludeId) ki kell tudni zárni update esetén
    public function findOverlapping(int $userId, string $start, string $end, ?int $excludeId = null): Collection;

    public function updateStatus(LeaveRequest $request, string $status, ?string $comment = null): bool;
}