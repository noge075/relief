<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkSchedule extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'name', 
        'weekly_pattern',
        'start_time',
        'end_time'
    ];

    protected $casts = [
        'weekly_pattern' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isWorkday(Carbon $date): bool
    {
        $dayName = strtolower($date->format('l'));
        return isset($this->weekly_pattern[$dayName]) && $this->weekly_pattern[$dayName] > 0;
    }

    public function getWorkHoursForDay(Carbon $date): float
    {
        $dayName = strtolower($date->format('l'));
        return $this->weekly_pattern[$dayName] ?? 0;
    }
}
