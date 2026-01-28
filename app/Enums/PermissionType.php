<?php

namespace App\Enums;

enum PermissionType: string
{
    // User Management
    case VIEW_USERS = 'view users';
    case VIEW_ALL_USERS = 'view all users';
    case CREATE_USERS = 'create users';
    case EDIT_USERS = 'edit users';
    case DELETE_USERS = 'delete users';
    case RESTORE_USERS = 'restore users';
    case VIEW_ANY_USER_PROFILE = 'view any user profile';
    case MANAGE_USER_DOCUMENTS = 'manage user documents';

    // Department Management
    case MANAGE_DEPARTMENTS = 'manage departments';

    // Work Schedule Management
    case MANAGE_WORK_SCHEDULES = 'manage work schedules';
    case ASSIGN_WORK_SCHEDULES = 'assign work schedules';

    // Leave Management
    case VIEW_LEAVE_BALANCES = 'view leave balances';
    case VIEW_ALL_LEAVE_BALANCES = 'view all leave balances';
    case ADJUST_LEAVE_BALANCES = 'adjust leave balances';
    
    case VIEW_LEAVE_REQUESTS = 'view leave requests';
    case VIEW_ALL_LEAVE_REQUESTS = 'view all leave requests';
    case VIEW_LEAVE_REQUEST_DETAILS = 'view leave request details';
    case CREATE_LEAVE_REQUESTS = 'create leave requests';
    case CREATE_PAST_LEAVE_REQUESTS = 'create past leave requests';
    case APPROVE_LEAVE_REQUESTS = 'approve leave requests';
    case DELETE_LEAVE_REQUESTS = 'delete leave requests';

    // Attendance
    case VIEW_ATTENDANCE = 'view attendance';
    case MANAGE_ATTENDANCE = 'manage attendance';
    case EXPORT_ATTENDANCE = 'export attendance';
    case VIEW_STATUS_BOARD = 'view status board';

    // Documents
    case VIEW_DOCUMENTS = 'view documents';
    case UPLOAD_DOCUMENTS = 'upload documents';
    case DELETE_DOCUMENTS = 'delete documents';

    // Payroll
    case VIEW_PAYROLL_DATA = 'view payroll data';
    case MANAGE_MONTHLY_CLOSURES = 'manage monthly closures';

    // System
    case VIEW_AUDIT_LOGS = 'view audit logs';
    case MANAGE_SETTINGS = 'manage settings';
}
