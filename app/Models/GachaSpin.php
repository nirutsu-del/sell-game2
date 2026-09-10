<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GachaSpin extends Model
{
    protected $fillable = [
        'user_id',
        'gacha_box_id',
        'gacha_item_id',
        'purchase_history_id',
        'price_paid',
        'request_id',
        'result_data',
    ];

    protected function casts(): array
    {
        return [
            'price_paid' => 'decimal:2',
            'result_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(GachaBox::class, 'gacha_box_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(GachaItem::class, 'gacha_item_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PurchaseHistory::class, 'purchase_history_id');
    }
}
