<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ContactMessage extends Model {
    protected $fillable=['user_id','name','email','subject','message'];
    protected function casts(): array { return ['read_at'=>'datetime','resolved_at'=>'datetime']; }
    public function statusLabel(): string { return $this->resolved_at ? 'จัดการแล้ว' : ($this->read_at ? 'อ่านแล้ว · รอจัดการ' : 'ข้อความใหม่'); }
}
