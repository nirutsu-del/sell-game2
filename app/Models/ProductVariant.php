<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProductVariant extends Model {
    protected $fillable = ['product_id','name','price','stock','is_active'];
    protected function casts(): array { return ['price'=>'decimal:2','stock'=>'integer','is_active'=>'boolean']; }
    public function product() { return $this->belongsTo(Product::class); }
}
