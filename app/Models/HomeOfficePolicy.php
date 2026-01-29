<?php

namespace App\Models;

use App\Enums\HomeOfficePolicyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HomeOfficePolicy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'limit_days',
        'period_days',
    ];

    protected $casts = [
        'limit_days' => 'integer',
        'period_days' => 'integer',
        'type' => HomeOfficePolicyType::class,
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}