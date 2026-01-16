<?php

namespace App\Repositories\Contracts;

use App\Models\SpecialDay;
use Illuminate\Database\Eloquent\Collection;

interface SpecialDayRepositoryInterface extends BaseRepositoryInterface
{
    public function getByYear(int $year): Collection;
    public function findByDate(string $date): ?SpecialDay;
    public function getInRange(string $startDate, string $endDate): Collection;
}
