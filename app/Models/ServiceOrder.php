<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ServiceOrder extends Model {
    protected $guarded = ['id'];
    protected function casts(): array { return ['total'=>'decimal:2']; }
    public function user() { return $this->belongsTo(User::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function events() { return $this->hasMany(OrderStatusEvent::class)->orderBy('id'); }
    public function statusLabel(): string { return ['pending'=>'รอดำเนินการ','processing'=>'กำลังดำเนินการ','completed'=>'สำเร็จ','refunded'=>'คืนเงินแล้ว'][$this->status] ?? $this->status; }
}
