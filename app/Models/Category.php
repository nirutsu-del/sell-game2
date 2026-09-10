<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Category extends Model {
    protected $fillable = ['name','slug','image','parent_id','is_featured','sort_order'];
    public function gameAccounts() { return $this->hasMany(GameAccount::class); }
    public function products() { return $this->hasMany(Product::class); }
    public function parent() { return $this->belongsTo(self::class,'parent_id'); }
    public function children() { return $this->hasMany(self::class,'parent_id')->orderBy('sort_order'); }
}
