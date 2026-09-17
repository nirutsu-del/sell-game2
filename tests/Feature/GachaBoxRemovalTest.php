<?php

namespace Tests\Feature;

use App\Models\{GachaBox, GachaItem, GachaSpin, User};
use App\Services\OrderHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaBoxRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_used_box_preserves_history_and_returns_to_the_list(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'balance' => 100]);
        $box = GachaBox::create(['name' => 'Archived box', 'price_per_spin' => 20, 'is_active' => true]);
        $used = GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 50]);
        $active = GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 10, 'drop_rate' => 50]);
        $spin = GachaSpin::create(['user_id' => $admin->id, 'gacha_box_id' => $box->id, 'gacha_item_id' => $used->id, 'price_paid' => 20]);
        $used->delete();

        $this->actingAs($admin)->get(route('notifications.count'))->assertOk();
        $this->delete(route('admin.gacha.destroy', $box))
            ->assertRedirect(route('admin.gacha.index'))->assertSessionHas('success');

        $this->assertSoftDeleted($box);
        $this->assertSoftDeleted($used);
        $this->assertSoftDeleted($active);
        $this->assertTrue($spin->fresh()->box->is($box));
        $this->assertTrue($spin->fresh()->item->is($used));
        $history = app(OrderHistoryService::class)->paginate($admin, ['type' => 'gacha']);
        $this->assertSame('Archived box', $history->items()[0]['title']);
        $this->assertSame('เครดิต ฿5.00', $history->items()[0]['detail']);
        $this->get(route('admin.gacha.index'))->assertOk()->assertDontSee('Archived box');
        $this->get(route('gacha.index'))->assertOk()->assertDontSee('Archived box');
        $this->get(route('gacha.show', $box))->assertNotFound();
        $this->postJson(route('gacha.spin', $box))->assertNotFound();
        $this->assertSame(100.0, (float) $admin->fresh()->balance);
        $this->assertDatabaseCount('gacha_spins', 1);
    }

    public function test_admin_can_delete_an_empty_box(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $box = GachaBox::create(['name' => 'Empty box', 'price_per_spin' => 20]);
        $this->actingAs($admin)->delete(route('admin.gacha.destroy', $box))
            ->assertRedirect(route('admin.gacha.index'));
        $this->assertSoftDeleted($box);
    }

    public function test_customer_cannot_delete_a_box(): void
    {
        $user = User::factory()->create();
        $box = GachaBox::create(['name' => 'Protected box', 'price_per_spin' => 20]);
        $this->actingAs($user)->delete(route('admin.gacha.destroy', $box))->assertForbidden();
        $this->assertNotSoftDeleted($box);
    }
}
