<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;

class StoreNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public string $target,
        public int $targetId,
    ) {}

    public function via(object $notifiable): array { return ['database']; }

    public function toDatabase(object $notifiable): array
    {
        return ['title'=>$this->title,'message'=>$this->message,'target'=>$this->target,'target_id'=>$this->targetId];
    }
}
