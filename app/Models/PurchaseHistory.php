<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PurchaseHistory extends Model { protected $fillable=['user_id','game_account_id','price_paid','account_data_delivered','source']; protected $hidden=['account_data_delivered']; public function account(){return $this->belongsTo(GameAccount::class,'game_account_id');} public function user(){return $this->belongsTo(User::class);} }
