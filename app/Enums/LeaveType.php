<?php

namespace App\Enums;

enum LeaveType: string
{
    case VACATION = 'vacation';
    case SICK = 'sick';
    case HOME_OFFICE = 'home_office';
    case UNPAID = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::VACATION => __('Vacation'),
            self::SICK => __('Sick Leave'),
            self::HOME_OFFICE => __('Home Office'),
            self::UNPAID => __('Unpaid Leave'),
        };
    }
}
