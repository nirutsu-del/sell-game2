<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GachaBox extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price_per_spin',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_spin' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(GachaItem::class);
    }

    public function spins(): HasMany
    {
        return $this->hasMany(GachaSpin::class);
    }

    public function availableAccountsCount(): int
    {
        return $this->items()
            ->where('drop_rate', '>', 0)
            ->where('reward_type', 'game_account')
            ->whereHas('account', fn ($q) => $q->where('status', 'available'))
            ->count();
    }

    public function eligibleItems(): \Illuminate\Support\Collection
    {
        $this->loadMissing('items.account');
        return $this->items->filter(fn ($item) => $item->drop_rate > 0 &&
            ($item->reward_type === 'credit' || ($item->reward_type === 'game_account' && $item->account?->status === 'available')));
    }
}
