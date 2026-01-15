<?php

namespace App\Enums;

enum LeaveType: string {
    case VACATION = 'vacation';
    case SICK = 'sick';
    case HOME_OFFICE = 'home_office';
    case OTHER = 'other';
}