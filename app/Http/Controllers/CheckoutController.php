<?php

namespace App\Http\Controllers;

use App\Models\GameAccount;
use App\Services\StoreService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function buy(Request $r, GameAccount $account, StoreService $store)
    {
        $purchase = $store->buy($r->user(), $account->id);
        return redirect()->route('purchases.show', $purchase)->with('success', 'ชำระเงินสำเร็จ ข้อมูลไอดีอยู่ด้านล่าง');
    }

    public function spin(Request $r, int $box, StoreService $store)
    {
        $data = $r->validate(['request_id' => ['nullable', 'uuid']]);
        $result = $store->spin($r->user(), $box, $data['request_id'] ?? null);

        if ($r->expectsJson() || $r->ajax()) {
            return response()->json([
                'success' => true,
                ...$result,
            ]);
        }

        return back()->with('success', $result['result']);
    }

    public function purchase(Request $r, int $id)
    {
        $p = $r->user()->purchases()->with('account')->findOrFail($id);
        return view('purchases.show', [
            'purchase' => $p,
            'credentials' => json_decode(decrypt($p->account_data_delivered), true),
        ]);
    }
}
