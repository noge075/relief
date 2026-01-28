<?php

namespace App\Models;

use App\Enums\AttendanceStatusType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceLog extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 
        'date', 
        'check_in', 
        'check_out',
        'status', 
        'worked_hours'
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'worked_hours' => 'decimal:2',
        'status' => AttendanceStatusType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
