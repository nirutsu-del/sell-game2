<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GameAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function index()
    {
        return view('admin.accounts.index', [
            'accounts' => GameAccount::with('category')->latest()->paginate(30),
        ]);
    }

    public function create()
    {
        return view('admin.account-form', [
            'categories' => Category::all(),
            'account' => new GameAccount(),
        ]);
    }

    public function edit(GameAccount $account)
    {
        return view('admin.account-form', [
            'categories' => Category::all(),
            'account' => $account,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'username' => ['required', 'string', 'max:255'],
            'password_value' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $images = collect($request->file('images', []))
            ->map(fn ($image) => $image->store('game-accounts', 'public'))
            ->all();

        GameAccount::create([
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'images' => $images ?: null,
            'credentials_data' => [
                'username' => $data['username'],
                'password' => $data['password_value'],
                'code' => $data['code'] ?? null,
            ],
        ]);

        return redirect()->route('admin.accounts.index')->with('success', 'เพิ่มไอดีเรียบร้อย');
    }

    public function update(Request $request, GameAccount $account)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:available,reserved,sold'],
            'username' => ['nullable', 'string', 'max:255'],
            'password_value' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $attributes = collect($data)->only(['category_id', 'title', 'description', 'price', 'status'])->all();
        if ($request->hasFile('images')) {
            $newImages = collect($request->file('images'))
                ->map(fn ($image) => $image->store('game-accounts', 'public'))
                ->all();
            Storage::disk('public')->delete($account->images ?? []);
            $attributes['images'] = $newImages;
        }

        if ($data['username'] ?? null || $data['password_value'] ?? null || $data['code'] ?? null) {
            $credentials = $account->credentials();
            $attributes['credentials_data'] = [
                'username' => $data['username'] ?: $credentials['username'],
                'password' => $data['password_value'] ?: $credentials['password'],
                'code' => $data['code'] ?: ($credentials['code'] ?? null),
            ];
        }

        $account->update($attributes);

        return redirect()->route('admin.accounts.index')->with('success', 'บันทึกการแก้ไขไอดีเกมแล้ว');
    }

    public function destroy(GameAccount $account)
    {
        abort_if($account->status === 'sold' || $account->purchase()->exists(), 422, 'ไม่สามารถลบไอดีที่มีประวัติการขายได้');

        Storage::disk('public')->delete($account->images ?? []);
        $account->delete();

        return redirect()->route('admin.accounts.index')->with('success', 'ลบไอดีเกมแล้ว');
    }
}
