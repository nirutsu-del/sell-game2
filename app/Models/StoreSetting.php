<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StoreSetting extends Model {
    protected $fillable = ['name','description','logo','promptpay_qr','truemoney_qr','promptpay_instructions','truemoney_instructions','facebook_url','line_url','discord_url','opening_hours','floating_contact_enabled','announcement','announcement_enabled'];
    protected function casts(): array {
        return ['floating_contact_enabled'=>'boolean','announcement_enabled'=>'boolean'];
    }
    public function contactLinks(): array {
        $links = [];
        foreach (['facebook'=>'Facebook','line'=>'LINE','discord'=>'Discord'] as $key=>$label) {
            if ($this->getAttribute($key.'_url')) $links[] = ['label'=>$label,'url'=>$this->getAttribute($key.'_url')];
        }
        return $links;
    }
    protected $attributes = [
        'name'=>'GAMEVAULT',
        'description'=>'เลือกไอดีเกมและบริการในร้าน เติมเงินผ่าน Wallet แล้วติดตามทุกคำสั่งซื้อได้ในบัญชีของคุณ',
    ];
    public static function current(): self { return static::find(1) ?? new static(); }
    public function qrUrl(string $method): ?string {
        $path = $this->getAttribute($method.'_qr');
        return $path ? asset('storage/'.$path) : config('services.'.$method.'.qr_image');
    }
}
