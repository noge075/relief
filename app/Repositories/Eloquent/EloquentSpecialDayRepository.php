<?php

namespace App\Repositories\Eloquent;

use App\Models\SpecialDay;
use App\Repositories\Contracts\SpecialDayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentSpecialDayRepository extends BaseRepository implements SpecialDayRepositoryInterface
{
    public function __construct(SpecialDay $model)
    {
        parent::__construct($model);
    }

    public function getByYear(int $year): Collection
    {
        return $this->model::whereYear('date', $year)->get();
    }

    public function findByDate(string $date): ?SpecialDay
    {
        return $this->model::where('date', $date)->first();
    }

    public function getInRange(string $startDate, string $endDate): Collection
    {
        return $this->model::whereBetween('date', [$startDate, $endDate])->get();
    }
}
