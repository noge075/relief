<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Notifications\DatabaseNotification;

class NotificationCenter extends Component
{
    public $unreadNotifications;
    public $readNotifications;
    public $showNotifications = false;

    protected $listeners = ['notificationSent' => 'loadNotifications'];

    public function loadNotifications(): void
    {
        if (auth()->check()) {
            $this->unreadNotifications = auth()->user()->unreadNotifications()->limit(5)->get();
            $this->readNotifications = auth()->user()->readNotifications()->limit(5)->get();
        } else {
            $this->unreadNotifications = collect();
            $this->readNotifications = collect();
        }
    }

    public function markAsRead($id): void
    {
        if (auth()->check()) {
            auth()->user()->notifications()->where('id', $id)->first()->markAsRead();
            $this->loadNotifications();
        }
    }

    public function markAllAsRead(): void
    {
        if (auth()->check()) {
            auth()->user()->unreadNotifications->markAsRead();
            $this->loadNotifications();
        }
    }

    public function deleteNotification($id): void
    {
        if (auth()->check()) {
            auth()->user()->notifications()->where('id', $id)->first()->delete();
            $this->loadNotifications();
        }
    }

    public function render()
    {
        $this->loadNotifications();
        return view('livewire.notification-center');
    }
}
