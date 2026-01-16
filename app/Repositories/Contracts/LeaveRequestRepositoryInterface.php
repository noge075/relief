<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface LeaveRequestRepositoryInterface extends BaseRepositoryInterface
{
    // Visszaadja egy adott user összes kérelmét (szűrhető státuszra)
    public function getForUser(int $userId, ?string $status = null): Collection;

    // Naptárhoz: adott időszakra eső kérelmek
    public function getForUserInPeriod(int $userId, string $start, string $end): Collection;

    // Vezetőnek: a hozzá tartozó jóváhagyásra váró kérelmek (Collection - régi)
    public function getPendingForManager(int $managerId): Collection;
    
    // Új: Paginált lista a jóváhagyásokhoz (ha managerId null, akkor mindenki)
    public function getPendingRequests(?int $managerId = null, int $perPage = 10): LengthAwarePaginator;

    // Átfedés vizsgálat: van-e már kérelme az adott időszakra?
    // Fontos: a saját magát (excludeId) ki kell tudni zárni update esetén
    public function findOverlapping(int $userId, string $start, string $end, ?int $excludeId = null): Collection;

    public function updateStatus(LeaveRequest $request, string $status, ?string $comment = null): bool;
}
