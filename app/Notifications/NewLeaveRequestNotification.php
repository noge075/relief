<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaveRequestNotification extends Notification implements ShouldQueue
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
        $user = $this->leaveRequest->user;
        $type = $this->leaveRequest->type->value;
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return (new MailMessage)
            ->subject(__('New Leave Request: :name', ['name' => $user->name]))
            ->line(__(':name has submitted a new leave request.', ['name' => $user->name]))
            ->line(__('Type: :type', ['type' => ucfirst($type)]))
            ->line(__('Date: :start to :end', ['start' => $start, 'end' => $end]))
            ->line(__('Reason: :reason', ['reason' => $this->leaveRequest->reason ?? '-']))
            ->action(__('View Request'), route('approvals.index'));
    }
}
