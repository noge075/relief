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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');
        $type = $this->leaveRequest->type->label();

        return (new MailMessage)
            ->subject(__('Leave Request Rejected'))
            ->line(__('Your :type request has been rejected.', ['type' => $type]))
            ->line(__('Date: :start to :end', ['start' => $start, 'end' => $end]))
            ->line(__('Reason for rejection: :reason', ['reason' => $this->leaveRequest->manager_comment]))
            ->action(__('View My Requests'), route('my-requests.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');
        $type = $this->leaveRequest->type->label();

        return [
            'title' => __('Leave Request Rejected'),
            'message' => __('Your :type request from :start to :end has been rejected. Reason: :reason', [
                'type' => $type,
                'start' => $start,
                'end' => $end,
                'reason' => $this->leaveRequest->manager_comment,
            ]),
            'url' => route('my-requests.index'),
            'request_id' => $this->leaveRequest->id,
        ];
    }
}
