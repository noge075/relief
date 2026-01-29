<?php

namespace App\Enums;

enum AttendanceStatusType: string
{
    case PRESENT = 'present';
    case VACATION = 'vacation';
    case SICK_LEAVE = 'sick_leave';
    case HOME_OFFICE = 'home_office';
    case HOLIDAY = 'holiday';
    case OFF = 'off';
    case UNPAID = 'unpaid';
    case SCHEDULED = 'scheduled';
    case WEEKEND = 'weekend';

    public function label(): string
    {
        return match ($this) {
            self::PRESENT => __('Present'),
            self::VACATION => __('Vacation'),
            self::SICK_LEAVE => __('Sick Leave'),
            self::HOME_OFFICE => __('Home Office'),
            self::HOLIDAY => __('Holiday'),
            self::OFF => __('Off'),
            self::UNPAID => __('Unpaid Leave'),
            self::SCHEDULED => __('Scheduled'),
            self::WEEKEND => __('Weekend'),
        };
    }
}
