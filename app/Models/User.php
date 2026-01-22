<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EmploymentType;
use App\Enums\PermissionType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasRoles, LogsActivity, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'last_name', 'first_name',
        'email', 'password',
        'employment_type', 'department_id', 'manager_id',
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
            get: fn (mixed $value, array $attributes) => trim(($attributes['last_name'] ?? '') . ' ' . ($attributes['first_name'] ?? '')),
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

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function workSchedule() {
        return $this->belongsTo(WorkSchedule::class);
    }

    // Hierarchia
    public function manager() {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates() {
        return $this->hasMany(User::class, 'manager_id');
    }

    // Szabadság modul
    public function leaveBalances() {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests() {
        return $this->hasMany(LeaveRequest::class);
    }

    // Dokumentumok
    public function attendanceDocuments() {
        return $this->hasMany(AttendanceDocument::class);
    }
    
    // Impersonate jogosultság
    public function canImpersonate()
    {
        return $this->can(PermissionType::VIEW_USERS->value) || $this->can(PermissionType::VIEW_ALL_USERS->value);
    }

    public function canBeImpersonated()
    {
        return !$this->hasRole('super-admin');
    }
}
