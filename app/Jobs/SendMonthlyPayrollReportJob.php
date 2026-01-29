<?php

namespace App\Jobs;

use App\Enums\RoleType;
use App\Notifications\MonthlyPayrollReportNotification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendMonthlyPayrollReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $year,
        public int $month
    ) {}

    public function handle(): void
    {
        $superAdmins = User::role(RoleType::SUPER_ADMIN->value)->get();

        if ($superAdmins->isEmpty()) {
            return;
        }

        Notification::send($superAdmins, new MonthlyPayrollReportNotification(
            $this->year,
            $this->month
        ));
    }
}
