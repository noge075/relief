<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EmploymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'password',
        'employment_type', 'department_id', 'manager_id',
        'work_schedule_id', 'hired_at', 'is_active'
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

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
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
}
