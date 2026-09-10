<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GachaBox;
use App\Models\GachaItem;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_gacha_box_and_add_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'RoV', 'slug' => 'rov']);
        $account = GameAccount::create([
            'category_id' => $category->id,
            'title' => 'ไอดีทดสอบ RoV แรร์',
            'price' => 500,
            'credentials_data' => ['username' => 'testuser', 'password' => 'testpass'],
            'status' => 'available',
        ]);

        // 1. Create Gacha Box
        $res = $this->actingAs($admin)->post(route('admin.gacha.store'), [
            'name' => 'กล่องสุ่ม ROV เทพ',
            'description' => 'ลุ้นรับไอดีฮีโร่ครบ',
            'price_per_spin' => 20,
            'is_active' => 1,
        ]);

        $box = GachaBox::firstOrFail();
        $this->assertSame('กล่องสุ่ม ROV เทพ', $box->name);

        // 2. Add Game Account reward
        $this->actingAs($admin)->post(route('admin.gacha.items.add', $box), [
            'reward_type' => 'game_account',
            'game_account_id' => $account->id,
            'drop_rate' => 10,
        ])->assertSessionHas('success');

        // 3. Add Credit refund reward
        $this->actingAs($admin)->post(route('admin.gacha.items.add', $box), [
            'reward_type' => 'credit',
            'credit_amount' => 5,
            'drop_rate' => 90,
        ])->assertSessionHas('success');

        $this->assertCount(2, $box->items);
        $this->assertSame(1, $box->availableAccountsCount());
    }

    public function test_user_cannot_spin_with_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 10]);
        $box = GachaBox::create([
            'name' => 'กล่องสุ่มพรีเมียม',
            'price_per_spin' => 50,
            'is_active' => true,
        ]);

        GachaItem::create([
            'gacha_box_id' => $box->id,
            'reward_type' => 'credit',
            'credit_amount' => 10,
            'drop_rate' => 100,
        ]);

        $response = $this->actingAs($user)->postJson(route('gacha.spin', $box));
        $response->assertStatus(422);
        $this->assertSame(10.0, (float)$user->fresh()->balance);
    }

    public function test_user_can_spin_and_win_game_account(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $category = Category::create(['name' => 'Valorant', 'slug' => 'valorant']);
        $account = GameAccount::create([
            'category_id' => $category->id,
            'title' => 'Valorant มีดแรร์',
            'price' => 300,
            'credentials_data' => ['username' => 'valouser', 'password' => 'valopass'],
            'status' => 'available',
        ]);

        $box = GachaBox::create([
            'name' => 'กล่องสุ่ม Valorant',
            'price_per_spin' => 30,
            'is_active' => true,
        ]);

        GachaItem::create([
            'gacha_box_id' => $box->id,
            'reward_type' => 'game_account',
            'game_account_id' => $account->id,
            'drop_rate' => 100,
        ]);

        $response = $this->actingAs($user)->postJson(route('gacha.spin', $box));
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'reward_type' => 'game_account',
                'account_title' => 'Valorant มีดแรร์',
            ]);

        // Balance deducted: 100 - 30 = 70
        $this->assertSame('70.00', $user->fresh()->balance);

        // Account status is sold
        $this->assertSame('sold', $account->fresh()->status);

        // Available count becomes 0
        $this->assertSame(0, $box->availableAccountsCount());

        // Purchase history created
        $this->assertDatabaseHas('purchase_histories', [
            'user_id' => $user->id,
            'game_account_id' => $account->id,
            'source' => 'gacha',
        ]);
    }
}
