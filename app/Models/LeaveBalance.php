<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeaveBalance extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'year',
        'type',
        'allowance',
        'used',
        'remaining',
    ];

    protected $casts = [
        'allowance' => 'int',
        'used' => 'int',
        'remaining' => 'int',
    ];


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
