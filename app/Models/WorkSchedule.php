<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    protected $fillable = ['name', 'weekly_pattern'];

    protected $casts = [
        'weekly_pattern' => 'array',
    ];

    public function users() {
        return $this->hasMany(User::class);
    }
}
