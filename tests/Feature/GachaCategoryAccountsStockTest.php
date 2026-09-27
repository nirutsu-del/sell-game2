<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GachaBox;
use App\Models\GachaItem;
use App\Models\GameAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaCategoryAccountsStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_gacha_box_counts_available_accounts_from_game_category(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $valorant = Category::create(['name' => 'Valorant', 'slug' => 'valorant']);
        $freefire = Category::create(['name' => 'Free Fire', 'slug' => 'free-fire']);

        // Create 3 available accounts for Valorant, 1 sold
        for ($i = 1; $i <= 3; $i++) {
            GameAccount::create([
                'category_id' => $valorant->id,
                'title' => "Valorant Acc $i",
                'price' => 100 * $i,
                'status' => 'available',
                'credentials_data' => ['user' => "val$i"],
            ]);
        }
        GameAccount::create([
            'category_id' => $valorant->id,
            'title' => "Valorant Sold",
            'price' => 200,
            'status' => 'sold',
            'credentials_data' => ['user' => 'sold'],
        ]);

        // Create 5 available accounts for Free Fire
        for ($i = 1; $i <= 5; $i++) {
            GameAccount::create([
                'category_id' => $freefire->id,
                'title' => "FF Acc $i",
                'price' => 50 * $i,
                'status' => 'available',
                'credentials_data' => ['user' => "ff$i"],
            ]);
        }

        // Box 1: Valorant box with category_id
        $valBox = GachaBox::create([
            'name' => 'สุ่มรหัส Valorant',
            'category_id' => $valorant->id,
            'price_per_spin' => 20,
            'is_active' => true,
        ]);
        // Add 1 item pointing to a valorant account
        $firstVal = GameAccount::where('category_id', $valorant->id)->where('status', 'available')->first();
        GachaItem::create([
            'gacha_box_id' => $valBox->id,
            'game_account_id' => $firstVal->id,
            'reward_type' => 'game_account',
            'drop_rate' => 10,
        ]);

        // Box 2: Free Fire box matching by name
        $ffBox = GachaBox::create([
            'name' => 'สุ่มรหัส Free Fire',
            'price_per_spin' => 10,
            'is_active' => true,
        ]);
        $firstFF = GameAccount::where('category_id', $freefire->id)->first();
        GachaItem::create([
            'gacha_box_id' => $ffBox->id,
            'game_account_id' => $firstFF->id,
            'reward_type' => 'game_account',
            'drop_rate' => 10,
        ]);

        // Verify gameAccountsInStockCount reflects available accounts of each game
        $this->assertSame(3, $valBox->gameAccountsInStockCount());
        $this->assertSame(5, $ffBox->gameAccountsInStockCount());

        $this->get(route('gacha.index'))->assertOk()
            ->assertSee('เหลือ 3 ไอดี')
            ->assertSee('เหลือ 5 ไอดี')
            ->assertDontSee('เหลือ 1 ไอดี');

        // Verify Admin index table shows the counts
        $response = $this->actingAs($admin)->get(route('admin.gacha.index'));
        $response->assertOk();
        $response->assertSee('3 ไอดี');
        $response->assertSee('5 ไอดี');
        $response->assertSee('เกม: Valorant');
        $response->assertSee('เกม: Free Fire');
    }

    public function test_admin_can_set_category_when_creating_and_updating_box(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $cat = Category::create(['name' => 'Rov', 'slug' => 'rov']);

        // Create
        $response = $this->actingAs($admin)->post(route('admin.gacha.store'), [
            'name' => 'สุ่มรหัส Rov เทพๆ',
            'category_id' => $cat->id,
            'price_per_spin' => 15,
            'is_active' => '1',
        ]);
        $response->assertRedirect();

        $box = GachaBox::where('name', 'สุ่มรหัส Rov เทพๆ')->firstOrFail();
        $this->assertSame($cat->id, $box->category_id);

        // Update
        $this->actingAs($admin)->put(route('admin.gacha.update', $box), [
            'name' => 'สุ่มรหัส Rov พรีเมียม',
            'category_id' => $cat->id,
            'price_per_spin' => 25,
            'is_active' => '1',
        ])->assertRedirect();

        $this->assertSame('สุ่มรหัส Rov พรีเมียม', $box->fresh()->name);
        $this->assertSame($cat->id, $box->fresh()->category_id);
    }
}
