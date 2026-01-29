<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:send-monthly-payroll-report')->monthlyOn(1, '01:00');
Schedule::command('app:generate-daily-logs')->dailyAt('01:00');
