<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return (new MailMessage)
            ->subject(__('Leave Request Rejected'))
            ->line(__('Your leave request has been rejected.'))
            ->line(__('Date: :start to :end', ['start' => $start, 'end' => $end]))
            ->line(__('Reason for rejection: :reason', ['reason' => $this->leaveRequest->manager_comment]))
            ->action(__('View My Requests'), route('my-requests.index'));
    }
}
