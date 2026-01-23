<?php

namespace App\Notifications;

use App\Models\LeaveRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestDeletedNotification extends Notification implements ShouldQueue
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
        $user = $this->leaveRequest->user;
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return (new MailMessage)
            ->subject(__('Leave Request Deleted: :name', ['name' => $user->name]))
            ->line(__(':name has deleted a pending leave request.', ['name' => $user->name]))
            ->line(__('Type: :type', ['type' => ucfirst($this->leaveRequest->type->value)]))
            ->line(__('Date: :start to :end', ['start' => $start, 'end' => $end]));
    }

    public function toDatabase(object $notifiable): array
    {
        $user = $this->leaveRequest->user;
        $start = $this->leaveRequest->start_date->format('Y-m-d');
        $end = $this->leaveRequest->end_date->format('Y-m-d');

        return [
            'title' => __('Leave Request Deleted'),
            'message' => __(':name has deleted a pending leave request (:type from :start to :end).', [
                'name' => $user->name,
                'type' => ucfirst($this->leaveRequest->type->value),
                'start' => $start,
                'end' => $end,
            ]),
            'url' => route('approvals.index'),
            'request_id' => $this->leaveRequest->id,
        ];
    }
}
