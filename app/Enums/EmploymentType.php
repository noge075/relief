<?php

namespace App\Enums;

enum EmploymentType: int {
    case STANDARD = 1; // Munkaviszony
    case HOURLY = 2;   // Megbízás / órabér
    case FIXED = 3;    // Megbízás / fix
    case STUDENT = 4;  // Diák

    public function label(): string
    {
        return match($this) {
            self::STANDARD => __('Standard'),
            self::HOURLY => __('Hourly'),
            self::FIXED => __('Fixed'),
            self::STUDENT => __('Student'),
        };
    }

    public function hasLeaveBalance(): bool
    {
        return match($this) {
            self::STANDARD => true,
            default => false,
        };
    }

    public function needsTimeTracking(): bool
    {
        return match($this) {
            self::HOURLY, self::STUDENT => true,
            default => false, // Standard is használhatja, de nem kötelező/elsődleges
        };
    }
}
