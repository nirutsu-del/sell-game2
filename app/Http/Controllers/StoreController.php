<?php

namespace App\Http\Controllers;

use App\Models\{Category, GameAccount, GachaBox, GachaSpin, News};
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index(Request $r)
    {
        $q = GameAccount::with('category')->where('status', 'available');
        if ($r->category) $q->where('category_id', $r->category);
        if ($r->min_price) $q->where('price', '>=', $r->min_price);
        if ($r->max_price) $q->where('price', '<=', $r->max_price);

        return view('store.index', [
            'accounts' => $q->latest()->paginate(12)->withQueryString(),
            'categories' => Category::all(),
        ]);
    }

    public function show(GameAccount $account)
    {
        abort_if($account->status === 'sold', 404);
        return view('store.show', compact('account'));
    }

    public function gacha()
    {
        $boxes = GachaBox::where('is_active', true)
            ->with(['items.account.category'])
            ->withCount('spins')
            ->get();

        $recentWinners = GachaSpin::with(['user', 'box', 'item.account'])
            ->whereHas('item', fn ($q) => $q->where('reward_type', 'game_account'))
            ->latest()
            ->limit(10)
            ->get();

        return view('gacha.index', compact('boxes', 'recentWinners'));
    }

    public function gachaShow(GachaBox $box)
    {
        abort_unless($box->is_active || (auth()->check() && auth()->user()->isAdmin()), 404);

        $box->load(['items.account.category']);

        $recentSpins = null;
        if (auth()->check()) {
            $recentSpins = GachaSpin::with(['item.account', 'purchase'])
                ->where('gacha_box_id', $box->id)
                ->where('user_id', auth()->id())
                ->latest()
                ->limit(10)
                ->get();
        }

        $boxWinners = GachaSpin::with(['user', 'item.account'])
            ->where('gacha_box_id', $box->id)
            ->whereHas('item', fn ($q) => $q->where('reward_type', 'game_account'))
            ->latest()
            ->limit(8)
            ->get();

        return view('gacha.show', compact('box', 'recentSpins', 'boxWinners'));
    }

    public function news()
    {
        return view('news.index', ['items' => News::where('is_published', true)->latest('published_at')->paginate(9)]);
    }

    public function newsShow(News $news)
    {
        abort_unless($news->is_published, 404);
        return view('news.show', compact('news'));
    }
}
