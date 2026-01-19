<?php

use App\Livewire\Approvals\ManageApprovals;
use App\Livewire\Employees\ManageEmployees;
use App\Livewire\Employees\ManageLeaveBalances;
use App\Livewire\Roles\ManageRoles;
use App\Livewire\SpecialDays\ManageSpecialDays;
use App\Livewire\StatusBoard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')
    ->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('/status-board', StatusBoard::class)->name('status-board');
    Route::get('/employees', ManageEmployees::class)->name('employees.index');
    Route::get('/employees/balances', ManageLeaveBalances::class)->name('employees.balances');
    Route::get('/approvals', ManageApprovals::class)->name('approvals.index');
    Route::get('/roles', ManageRoles::class)->name('settings.roles');
    Route::get('/special-days', ManageSpecialDays::class)->name('settings.special-days');
});

require __DIR__.'/settings.php';
