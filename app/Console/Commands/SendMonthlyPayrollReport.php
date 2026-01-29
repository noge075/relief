<?php

namespace App\Console\Commands;

use App\Jobs\SendMonthlyPayrollReportJob;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendMonthlyPayrollReport extends Command
{
    protected $signature = 'app:send-monthly-payroll-report';
    protected $description = 'Generates and emails the monthly payroll report for the previous month to super admins.';

    public function handle(): void
    {
        $this->info('Dispatching job to generate and send the monthly payroll report...');

        $previousMonth = Carbon::now()->subMonth();
        
        SendMonthlyPayrollReportJob::dispatch($previousMonth->year, $previousMonth->month);

        $this->info('Job dispatched successfully.');
    }
}
