<?php
namespace App\Services;
use App\Models\User;
use App\Notifications\StoreNotification;

class StoreNotifier
{
    public static function admins(string $title, string $message, string $target, int $id): void
    {
        User::where('role','admin')->select('id')->chunkById(100, function ($admins) use ($title,$message,$target,$id) {
            foreach ($admins as $admin) $admin->notify(new StoreNotification($title,$message,$target,$id));
        });
    }
}
