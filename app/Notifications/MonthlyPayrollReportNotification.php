<?php

namespace App\Notifications;

use App\Exports\MonthlyPayrollExport;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class MonthlyPayrollReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $year,
        public int $month
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $monthName = Carbon::createFromDate($this->year, $this->month)->translatedFormat('F');
        $filename = 'payroll_report_' . $this->year . '_' . str_pad($this->month, 2, '0', STR_PAD_LEFT) . '.xlsx';

        $fileContent = Excel::raw(new MonthlyPayrollExport($this->year, $this->month), \Maatwebsite\Excel\Excel::XLSX);

        return (new MailMessage)
            ->subject(__('Monthly Payroll Report for :month :year', ['month' => $monthName, 'year' => $this->year]))
            ->line(__('Attached is the monthly payroll report for :month :year.', ['month' => $monthName, 'year' => $this->year]))
            ->attachData($fileContent, $filename, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
