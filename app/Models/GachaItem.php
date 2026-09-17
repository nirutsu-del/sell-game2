<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GachaItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'gacha_box_id',
        'game_account_id',
        'reward_type',
        'credit_amount',
        'drop_rate',
    ];

    protected function casts(): array
    {
        return [
            'credit_amount' => 'decimal:2',
            'drop_rate' => 'decimal:4',
        ];
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(GachaBox::class, 'gacha_box_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GameAccount::class, 'game_account_id');
    }
}
