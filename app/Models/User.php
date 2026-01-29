<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Lab404\Impersonate\Models\Impersonate;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use Notifiable, TwoFactorAuthenticatable, HasRoles, LogsActivity, Impersonate, SoftDeletes, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_name', 'first_name',
        'email', 'password',
        'employment_type', 'manager_id',
        'work_schedule_id', 'hired_at', 'is_active',
        'signature_path',
        'id_card_number', 'tax_id', 'ssn', 'address', 'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'employment_type' => EmploymentType::class,
            'hired_at' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => trim(($attributes['last_name'] ?? '') . ' ' . ($attributes['first_name'] ?? '')),
        );
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $first = Str::substr($this->last_name ?? '', 0, 1);
        $second = Str::substr($this->first_name ?? '', 0, 1);

        return strtoupper($first . $second);
    }

    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('avatar');
    }

    /**
     * Get the departments the user belongs to.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'department_user', 'user_id', 'department_id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceDocuments()
    {
        return $this->hasMany(AttendanceDocument::class);
    }

    public function canImpersonate(): bool
    {
        return $this->can(PermissionType::VIEW_USERS->value) || $this->can(PermissionType::VIEW_ALL_USERS->value);
    }

    public function canBeImpersonated(): bool
    {
        return !$this->hasRole('super-admin');
    }

    public function homeOfficePolicy(): BelongsTo
    {
        return $this->belongsTo(HomeOfficePolicy::class);
    }
}
