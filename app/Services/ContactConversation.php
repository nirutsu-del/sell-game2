<?php

namespace App\Services;

use App\Models\{ContactMessage, User};
use App\Notifications\StoreNotification;
use Illuminate\Support\Facades\DB;

class ContactConversation
{
    public static function reply(ContactMessage $message, string $body, ?User $actor, bool $fromStaff, array $files = []): void
    {
        ContactAttachments::withUploads($files, fn($uploads) => DB::transaction(function () use ($message, $body, $actor, $fromStaff, $uploads) {
            $message = ContactMessage::lockForUpdate()->findOrFail($message->id);
            $reply = $message->replies()->create([
                'body' => $body,
                'user_id' => $actor?->id,
                'from_staff' => $fromStaff,
            ]);
            foreach ($uploads as $upload) $message->attachments()->create($upload+['contact_reply_id'=>$reply->id]);
            // A new reply reopens the conversation, including previously closed cases.
            $message->resolved_at = null;
            $message->resolved_by = null;
            $message->resolved_by_name = null;
            $message->read_at = $fromStaff ? now() : null;
            $message->waiting_on = $fromStaff ? 'customer' : 'staff';
            $message->touch();
            if ($fromStaff) {
                $message->user?->notify(new StoreNotification('ร้านตอบกลับเรื่อง C-'.$message->id,
                    'เปิดเรื่องติดต่อเพื่ออ่านคำตอบจากร้าน', 'contact', $message->id));
            } else {
                StoreNotifier::admins('ลูกค้าตอบกลับเรื่อง C-'.$message->id,
                    'มีข้อความใหม่ในเรื่องติดต่อ', 'admin_contact', $message->id);
            }
        }, 3));
    }
}
