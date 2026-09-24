<?php

use App\Models\Category;
use App\Models\GachaBox;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gacha_boxes', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('name')->constrained()->nullOnDelete();
        });

        // Automatically associate existing boxes with categories by box name or item account category
        $categories = Category::all();
        foreach (GachaBox::all() as $box) {
            // Check item account category
            $catId = $box->items()
                ->whereNotNull('game_account_id')
                ->join('game_accounts', 'gacha_items.game_account_id', '=', 'game_accounts.id')
                ->value('game_accounts.category_id');

            if (!$catId) {
                // Check name
                $matched = $categories->first(function ($c) use ($box) {
                    return str_contains(mb_strtolower($box->name), mb_strtolower($c->name));
                });
                $catId = $matched?->id;
            }

            if ($catId) {
                $box->category_id = $catId;
                $box->saveQuietly();
            }
        }
    }

    public function down(): void
    {
        Schema::table('gacha_boxes', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
