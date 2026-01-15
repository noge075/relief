<?php

namespace App\Repositories\Eloquent;

use App\Models\WorkSchedule;
use App\Repositories\Contracts\WorkScheduleRepositoryInterface;

class EloquentWorkScheduleRepository extends BaseRepository implements WorkScheduleRepositoryInterface
{
    public function __construct(WorkSchedule $model)
    {
        parent::__construct($model);
    }
}