<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class News extends Model { protected $fillable=['title','slug','content','image','is_published','published_at']; protected function casts():array{return ['is_published'=>'boolean','published_at'=>'datetime'];} }
