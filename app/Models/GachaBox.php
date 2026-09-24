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
        'category_id',
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

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GachaItem::class);
    }

    public function spins(): HasMany
    {
        return $this->hasMany(GachaSpin::class);
    }

    public function gameCategory(): ?Category
    {
        if ($this->relationLoaded('category') && $this->category) {
            return $this->category;
        }

        if (!empty($this->category_id)) {
            $cat = Category::find($this->category_id);
            if ($cat) {
                return $cat;
            }
        }

        $catId = $this->items()
            ->whereNotNull('game_account_id')
            ->join('game_accounts', 'gacha_items.game_account_id', '=', 'game_accounts.id')
            ->value('game_accounts.category_id');

        if ($catId) {
            return Category::find($catId);
        }

        return Category::all()->first(function ($cat) {
            return str_contains(mb_strtolower($this->name), mb_strtolower($cat->name));
        });
    }

    public function gameAccountsInStockCount(): int
    {
        $hasGameAccountReward = $this->items()
            ->where('reward_type', 'game_account')
            ->where('drop_rate', '>', 0)
            ->exists();

        if ($hasGameAccountReward || $this->category_id) {
            $cat = $this->gameCategory();
            if ($cat) {
                return (int) $cat->gameAccounts()->where('status', 'available')->count();
            }
        }

        return $this->availableAccountsCount();
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
