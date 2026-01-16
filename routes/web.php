<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard')
    ->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')
        ->name('dashboard');

    // Employee Routes
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', App\Livewire\Employees\Index::class)
            ->name('index');
    });
});

require __DIR__ . '/settings.php';
