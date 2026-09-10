<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GachaBox;
use App\Models\GachaItem;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GachaBoxController extends Controller
{
    public function index()
    {
        $boxes = GachaBox::withCount(['items', 'spins'])
            ->withSum('spins', 'price_paid')
            ->latest()
            ->paginate(15);

        return view('admin.gacha.index', compact('boxes'));
    }

    public function create()
    {
        return view('admin.gacha.form', [
            'box' => new GachaBox([
                'price_per_spin' => 20,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_spin' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('gacha-boxes', 'public');
        }

        $box = GachaBox::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_per_spin' => $data['price_per_spin'],
            'image' => $imagePath,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.gacha.items', $box)->with('success', 'สร้างกล่องสุ่มเรียบร้อยแล้ว กรุณาเพิ่มของรางวัลลงในกล่อง');
    }

    public function edit(GachaBox $gacha)
    {
        return view('admin.gacha.form', ['box' => $gacha]);
    }

    public function update(Request $request, GachaBox $gacha)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_per_spin' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $attributes = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_per_spin' => $data['price_per_spin'],
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('image')) {
            if ($gacha->image) {
                Storage::disk('public')->delete($gacha->image);
            }
            $attributes['image'] = $request->file('image')->store('gacha-boxes', 'public');
        }

        $gacha->update($attributes);

        return redirect()->route('admin.gacha.index')->with('success', 'บันทึกการแก้ไขกล่องสุ่มแล้ว');
    }

    public function destroy(GachaBox $gacha)
    {
        if ($gacha->image) {
            Storage::disk('public')->delete($gacha->image);
        }
        $gacha->delete();

        return redirect()->route('admin.gacha.index')->with('success', 'ลบกล่องสุ่มเรียบร้อยแล้ว');
    }

    public function items(GachaBox $gacha)
    {
        $box = $gacha->load(['items.account.category']);

        // Available game accounts that are not already assigned to an item in THIS box
        $usedAccountIds = $box->items->whereNotNull('game_account_id')->pluck('game_account_id');
        $availableAccounts = GameAccount::with('category')
            ->where('status', 'available')
            ->whereNotIn('id', $usedAccountIds)
            ->latest()
            ->get();

        $totalRate = (float)$box->items->sum('drop_rate');

        return view('admin.gacha.items', compact('box', 'availableAccounts', 'totalRate'));
    }

    public function addItem(Request $request, GachaBox $gacha)
    {
        $data = $request->validate([
            'reward_type' => ['required', 'in:game_account,credit'],
            'game_account_id' => ['required_if:reward_type,game_account', 'nullable', 'exists:game_accounts,id'],
            'credit_amount' => ['required_if:reward_type,credit', 'nullable', 'numeric', 'min:0.01'],
            'drop_rate' => ['required', 'numeric', 'min:0.0001', 'max:100'],
        ]);

        if ($data['reward_type'] === 'game_account') {
            $account = GameAccount::findOrFail($data['game_account_id']);
            if ($account->status !== 'available') {
                return back()->withErrors(['game_account_id' => 'ไอดีนี้ไม่ได้อยู่ในสถานะพร้อมขาย']);
            }
        }

        GachaItem::create([
            'gacha_box_id' => $gacha->id,
            'reward_type' => $data['reward_type'],
            'game_account_id' => $data['reward_type'] === 'game_account' ? $data['game_account_id'] : null,
            'credit_amount' => $data['reward_type'] === 'credit' ? $data['credit_amount'] : null,
            'drop_rate' => $data['drop_rate'],
        ]);

        return back()->with('success', 'เพิ่มของรางวัลลงในกล่องสำเร็จ');
    }

    public function deleteItem(GachaBox $gacha, GachaItem $item)
    {
        abort_if($item->gacha_box_id !== $gacha->id, 404);
        $item->delete();

        return back()->with('success', 'ลบของรางวัลออกจากกล่องแล้ว');
    }

    public function updateDropRates(Request $request, GachaBox $gacha)
    {
        $data = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($data['rates'] as $itemId => $rate) {
            GachaItem::where('id', $itemId)
                ->where('gacha_box_id', $gacha->id)
                ->update(['drop_rate' => $rate]);
        }

        return back()->with('success', 'อัปเดตอัตราโอกาสออก (Drop Rates) เรียบร้อยแล้ว');
    }
}
