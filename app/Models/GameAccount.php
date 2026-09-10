<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
class GameAccount extends Model {
    protected $fillable = ['category_id','title','description','price','credentials_data','images','status'];
    protected function casts(): array { return ['images' => 'array', 'price' => 'decimal:2']; }
    public function category() { return $this->belongsTo(Category::class); }
    public function purchase() { return $this->hasOne(PurchaseHistory::class); }
    public function setCredentialsDataAttribute(array|string $value): void { $this->attributes['credentials_data'] = Crypt::encryptString(is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value); }
    public function credentials(): array { return json_decode(Crypt::decryptString($this->attributes['credentials_data']), true, 512, JSON_THROW_ON_ERROR); }
}
