<?php
namespace App\Http\Controllers;

use App\Models\PurchaseHistory;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['game'=>'nullable|integer|min:1']);
        $owned = PurchaseHistory::where('user_id', $request->user()->id)->with('account.category')->latest()->get();
        $owned = $owned->filter(fn ($purchase) => $purchase->account !== null);
        $games = $owned->map(fn ($purchase) => $purchase->account->category)->filter()->unique('id')->values();
        $items = $request->filled('game') ? $owned->filter(fn ($purchase) => $purchase->account->category_id == $request->game) : $owned;
        return view('user.collection', compact('owned', 'games', 'items'));
    }
}
