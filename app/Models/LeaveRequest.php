<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Enums\LeaveType;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LeaveRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'manager_comment',
        'approver_id',
        'has_warning',
        'warning_message',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'type' => LeaveType::class,
        'status' => LeaveStatus::class,
        'has_warning' => 'boolean',
        'days_count' => 'int'
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
