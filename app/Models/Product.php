<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    protected $fillable = ['category_id','name','description','terms','image','recipient_label','is_active','is_featured'];
    protected function casts(): array { return ['is_active'=>'boolean','is_featured'=>'boolean']; }
    public function category() { return $this->belongsTo(Category::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
}
