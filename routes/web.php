<?php

use App\Livewire\Employees\ManageEmployees;
use App\Livewire\Roles\ManageRoles;
use App\Livewire\SpecialDays\ManageSpecialDays;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')
    ->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/employees', ManageEmployees::class)->name('employees.index');
    Route::get('/roles', ManageRoles::class)->name('settings.roles');
    Route::get('/special-days', ManageSpecialDays::class)->name('settings.special-days');
});

require __DIR__.'/settings.php';
