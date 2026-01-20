<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyClosureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $year, public int $month, public $closedBy)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $date = \Carbon\Carbon::createFromDate($this->year, $this->month, 1)->translatedFormat('Y F');

        return (new MailMessage)
            ->subject(__('Monthly Closure: :date', ['date' => $date]))
            ->line(__('The payroll period for :date has been closed by :name.', ['date' => $date, 'name' => $this->closedBy->name]))
            ->line(__('You can now download the monthly report.'))
            ->action(__('View Report'), route('payroll.report'));
    }
}
