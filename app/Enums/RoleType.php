<?php

namespace App\Enums;

enum RoleType: string
{
    case SUPER_ADMIN = 'super-admin';
    case HR = 'hr';
    case MANAGER = 'manager';
    case EMPLOYEE = 'employee';
    case PAYROLL = 'payroll';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => __('Super Admin'),
            self::HR => __('HR'),
            self::MANAGER => __('Manager'),
            self::EMPLOYEE => __('Employee'),
            self::PAYROLL => __('Payroll'),
        };
    }
}
