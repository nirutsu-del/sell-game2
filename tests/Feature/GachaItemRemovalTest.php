<?php

namespace Tests\Feature;

use App\Models\{GachaBox, GachaItem, GachaSpin, User};
use App\Services\OrderHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaItemRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_removing_a_used_reward_preserves_history_and_excludes_it_from_the_box(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'balance' => 100]);
        $box = GachaBox::create(['name' => 'Removal test', 'price_per_spin' => 20, 'is_active' => true]);
        $item = GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 100]);
        $spin = GachaSpin::create(['user_id' => $admin->id, 'gacha_box_id' => $box->id, 'gacha_item_id' => $item->id, 'price_paid' => 20]);

        $this->actingAs($admin)->from(route('admin.gacha.items', $box))
            ->delete(route('admin.gacha.items.delete', [$box, $item]))
            ->assertRedirect(route('admin.gacha.items', $box))->assertSessionHas('success');

        $this->assertSoftDeleted($item);
        $this->assertDatabaseHas('gacha_spins', ['id' => $spin->id, 'gacha_item_id' => $item->id]);
        $this->assertTrue($spin->fresh()->item->is($item));
        $this->assertCount(0, $box->fresh()->items);
        $this->assertCount(0, $box->fresh()->eligibleItems());
        $history = app(OrderHistoryService::class)->paginate($admin, ['type' => 'gacha']);
        $this->assertSame('เครดิต ฿5.00', $history->items()[0]['detail']);
        $this->get(route('admin.gacha.items', $box))->assertOk()->assertDontSee('delete-item-'.$item->id);
        $this->postJson(route('gacha.spin', $box))->assertStatus(422);
        $this->assertSame(100.0, (float) $admin->fresh()->balance);
        $this->assertDatabaseCount('gacha_spins', 1);
    }

    public function test_admin_can_remove_an_unused_reward(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $box = GachaBox::create(['name' => 'Unused reward', 'price_per_spin' => 20]);
        $item = GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 100]);

        // Background notification polling must not become the post-delete destination.
        $this->actingAs($admin)->get(route('notifications.count'))->assertOk();
        $this->delete(route('admin.gacha.items.delete', [$box, $item]))
            ->assertRedirect(route('admin.gacha.items', $box))->assertSessionHas('success');
        $this->assertSoftDeleted($item);
    }

    public function test_admin_cannot_remove_a_reward_from_another_box(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $box = GachaBox::create(['name' => 'First box', 'price_per_spin' => 20]);
        $otherBox = GachaBox::create(['name' => 'Other box', 'price_per_spin' => 20]);
        $item = GachaItem::create(['gacha_box_id' => $otherBox->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 100]);

        $this->actingAs($admin)->delete(route('admin.gacha.items.delete', [$box, $item]))->assertNotFound();
        $this->assertNotSoftDeleted($item);
    }
}
