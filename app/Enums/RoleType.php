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
            self::SUPER_ADMIN => 'Super Admin',
            self::HR => 'HR',
            self::MANAGER => 'Manager',
            self::EMPLOYEE => 'Employee',
            self::PAYROLL => 'Payroll',
        };
    }
}
