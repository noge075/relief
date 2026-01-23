<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkSchedule extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = ['name', 'weekly_pattern'];

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
}
