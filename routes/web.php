<?php

use App\Livewire\Approvals\ManageApprovals;
use App\Livewire\Attendance\MyAttendance;
use App\Livewire\Employees\ManageEmployees;
use App\Livewire\Employees\ManageLeaveBalances;
use App\Livewire\Payroll\MonthlyReport;
use App\Livewire\Roles\ManageRoles;
use App\Livewire\Settings\ManageWorkSchedules;
use App\Livewire\SpecialDays\ManageSpecialDays;
use App\Livewire\StatusBoard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')
    ->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/attendance', MyAttendance::class)->name('attendance.index');
    Route::get('/status-board', StatusBoard::class)->name('status-board');
    Route::get('/employees', ManageEmployees::class)->name('employees.index');
    Route::get('/employees/balances', ManageLeaveBalances::class)->name('employees.balances');
    Route::get('/approvals', ManageApprovals::class)->name('approvals.index');
    Route::get('/payroll', MonthlyReport::class)->name('payroll.report');
    Route::get('/work-schedules', ManageWorkSchedules::class)->name('work-schedules.index'); // Normál route
    Route::get('/roles', ManageRoles::class)->name('settings.roles');
    Route::get('/special-days', ManageSpecialDays::class)->name('settings.special-days');
});

require __DIR__.'/settings.php';
