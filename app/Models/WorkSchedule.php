<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class WorkSchedule extends Model
{
    use HasFactory, LogsActivity;

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
