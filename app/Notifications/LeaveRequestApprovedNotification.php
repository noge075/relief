<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestApprovedNotification extends Notification implements ShouldQueue
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

        return (new MailMessage)
            ->subject(__('Leave Request Approved'))
            ->line(__('Your leave request has been approved.'))
            ->line(__('Date: :start to :end', ['start' => $start, 'end' => $end]))
            ->action(__('View My Requests'), route('my-requests.index'));
    }

    public function toDatabase(object $notifiable): array
    {
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return [
            'title' => __('Leave Request Approved'),
            'message' => __('Your leave request from :start to :end has been approved.', [
                'start' => $start,
                'end' => $end,
            ]),
            'url' => route('my-requests.index'),
            'request_id' => $this->leaveRequest->id,
        ];
    }
}
