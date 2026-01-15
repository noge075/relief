<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveBalance;

interface LeaveBalanceRepositoryInterface extends BaseRepositoryInterface
{
    public function getBalance(int $userId, int $year, string $type): ?LeaveBalance;

    // Növeli a felhasznált napokat (igénylés elfogadásakor)
    public function incrementUsed(int $userId, int $year, string $type, float $days): void;

    // Csökkenti a felhasznált napokat (visszavonáskor)
    public function decrementUsed(int $userId, int $year, string $type, float $days): void;
}