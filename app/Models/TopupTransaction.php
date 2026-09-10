<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TopupTransaction extends Model { protected $fillable=['user_id','amount','payment_method','reference_no','slip_path','verification_payload','status']; protected function casts():array{return ['amount'=>'decimal:2','verification_payload'=>'array'];} public function user(){return $this->belongsTo(User::class);} }
