<?php

namespace App\Enums;

enum SpecialDayType: string
{
    case WORKDAY = 'workday';
    case HOLIDAY = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::WORKDAY => __('Workday'),
            self::HOLIDAY => __('Holiday'),
            default => null,
        };
    }
}
