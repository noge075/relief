<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ImpersonateController;
use App\Livewire\Approvals\ManageApprovals;
use App\Livewire\Attendance\MyAttendance;
use App\Livewire\CompanyDirectory;
use App\Livewire\Employees\ManageEmployees;
use App\Livewire\Employees\ManageLeaveBalances;
use App\Livewire\MyDocuments;
use App\Livewire\MyRequests;
use App\Livewire\OrganizationChart;
use App\Livewire\Payroll\MonthlyReport;
use App\Livewire\Roles\ManageRoles;
use App\Livewire\Settings\ManageAuditLogs;
use App\Livewire\Settings\ManageDepartments; // Új
use App\Livewire\Settings\ManageSettings;
use App\Livewire\Settings\ManageWorkSchedules;
use App\Livewire\SpecialDays\ManageSpecialDays;
use App\Livewire\StatusBoard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')
    ->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/attendance', MyAttendance::class)->name('attendance.index');
    Route::get('/attendance/download-pdf/{year}/{month}', [AttendanceController::class, 'downloadPdf'])->name('attendance.download-pdf');
    
    Route::get('/my-requests', MyRequests::class)->name('my-requests.index');
    Route::get('/my-documents', MyDocuments::class)->name('my-documents.index');
    Route::get('/company-directory', CompanyDirectory::class)->name('company-directory.index');
    Route::get('/status-board', StatusBoard::class)->name('status-board');
    Route::get('/employees', ManageEmployees::class)->name('employees.index');
    Route::get('/employees/balances', ManageLeaveBalances::class)->name('employees.balances');
    Route::get('/approvals', ManageApprovals::class)->name('approvals.index');
    Route::get('/organization', OrganizationChart::class)->name('organization.index');
    Route::get('/payroll', MonthlyReport::class)->name('payroll.report');
    Route::get('/work-schedules', ManageWorkSchedules::class)->name('work-schedules.index');
    Route::get('/departments', ManageDepartments::class)->name('departments.index');
    
    // Settings routes protected by permission
    Route::middleware(['can:manage settings'])->group(function () {
        Route::get('/roles', ManageRoles::class)->name('settings.roles');
        Route::get('/special-days', ManageSpecialDays::class)->name('settings.special-days');
        Route::get('/settings', ManageSettings::class)->name('settings.index');
    });
    
    Route::get('/audit-logs', ManageAuditLogs::class)->name('settings.audit-logs')->middleware('can:view audit logs');

    // Impersonate
    Route::get('/impersonate/stop', [ImpersonateController::class, 'stopImpersonating'])->name('impersonate.stop');
    Route::get('/impersonate/{user}', [ImpersonateController::class, 'impersonate'])->name('impersonate');
});

require __DIR__.'/settings.php';
