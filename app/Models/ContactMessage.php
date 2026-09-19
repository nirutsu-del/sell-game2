<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContactMessage extends Model {
    public const CATEGORIES = ['general'=>'สอบถามทั่วไป','topup'=>'เติมเงินไม่เข้า','account'=>'ปัญหาไอดีเกม','order'=>'ปัญหาคำสั่งซื้อ','gacha'=>'ปัญหาสุ่มรางวัล'];
    protected $fillable=['user_id','name','email','subject','message','category','order_reference'];
    public function attachments() { return $this->hasMany(ContactAttachment::class); }
    public function initialAttachments() { return $this->attachments()->whereNull('contact_reply_id'); }
    public function categoryLabel(): string { return self::CATEGORIES[$this->category] ?? self::CATEGORIES['general']; }
    public function replies() { return $this->hasMany(ContactReply::class); }
    public function user() { return $this->belongsTo(User::class); }
    protected function casts(): array { return ['read_at'=>'datetime','resolved_at'=>'datetime']; }
    public function statusLabel(): string { return $this->resolved_at ? 'จัดการแล้ว' : ($this->waiting_on === 'customer' ? 'รอลูกค้าตอบ' : 'รอร้านตอบ'); }
}
