<?php

namespace App\Console\Commands;

use App\Jobs\GenerateDailyAttendanceLogsJob;
use Illuminate\Console\Command;

class GenerateDailyAttendanceLogs extends Command
{
    protected $signature = 'app:generate-daily-logs';
    protected $description = 'Dispatch job to generate daily attendance logs for all active users.';

    public function handle()
    {
        $this->info('Dispatching job to generate daily attendance logs...');
        GenerateDailyAttendanceLogsJob::dispatch();
        $this->info('Job dispatched successfully.');
    }
}
