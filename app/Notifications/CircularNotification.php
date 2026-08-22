<?php

namespace App\Notifications;

use App\Models\Circular;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CircularNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Circular $circular,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'circular_id' => $this->circular->id,
            'title' => $this->circular->title,
            'priority' => $this->circular->priority,
            'message' => "Nueva circular: {$this->circular->title}",
        ];
    }
}
