<?php

namespace Tests\Feature;

use App\Models\{GachaBox, GachaItem, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GachaReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_receipt_survives_box_deletion_but_new_spins_are_blocked(): void
    {
        $user = User::factory()->create(['balance'=>100]);
        $box = GachaBox::create(['name'=>'Deleted', 'price_per_spin'=>30, 'is_active'=>true]);
        GachaItem::create(['gacha_box_id'=>$box->id,'reward_type'=>'credit','credit_amount'=>5,'drop_rate'=>1]);
        $url = route('gacha.spin', $box);
        $body = ['request_id'=>(string) Str::uuid()];
        $first = $this->actingAs($user)->postJson($url, $body)->assertOk();
        $this->actingAs(User::factory()->create(['role'=>'admin']))->delete(route('admin.gacha.destroy',$box))->assertRedirect();
        $retry = $this->actingAs($user)->postJson($url,$body)->assertOk()->assertJsonPath('next_rewards',[]);
        $this->assertSame($first->json('spin_id'),$retry->json('spin_id'));
        $this->post($url,$body)->assertRedirect(route('user.collection'));
        $this->postJson($url,['request_id'=>(string)Str::uuid()])->assertNotFound();
        $this->assertSame('75.00',$user->fresh()->balance);
        $this->assertDatabaseCount('gacha_spins',1);
        $this->assertDatabaseCount('wallet_transactions',2);
        $this->actingAs(User::factory()->create())->postJson($url,$body)->assertNotFound();
    }

    public function test_retry_returns_saved_reward_without_charging_again(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $box = GachaBox::create(['name' => 'Test', 'price_per_spin' => 30, 'is_active' => true]);
        GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 1]);
        $body = ['request_id' => (string) Str::uuid()];
        $first = $this->actingAs($user)->postJson(route('gacha.spin', $box), $body)->assertOk();
        $box->update(['is_active' => false]);
        $retry = $this->postJson(route('gacha.spin', $box), $body)->assertOk();
        $this->assertSame($first->json('spin_id'), $retry->json('spin_id'));
        $this->assertSame('75.00', $user->fresh()->balance);
        $this->assertDatabaseCount('gacha_spins', 1);
        $this->assertDatabaseCount('wallet_transactions', 2);
        $first->assertJsonMissingPath('credentials');
    }

    public function test_zero_weight_rewards_do_not_charge_wallet(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        $box = GachaBox::create(['name' => 'Empty', 'price_per_spin' => 30, 'is_active' => true]);
        GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 0]);
        $this->actingAs($user)->postJson(route('gacha.spin', $box))->assertUnprocessable();
        $this->assertSame('100.00', $user->fresh()->balance);
        $this->assertDatabaseCount('gacha_spins', 0);
    }

    public function test_room_renders_normalized_odds_and_recovery_history(): void
    {
        $box = GachaBox::create(['name' => 'Odds', 'price_per_spin' => 30, 'is_active' => true]);
        foreach ([1, 3] as $weight) {
            GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => $weight]);
        }
        $this->get(route('gacha.show', $box))->assertOk()->assertSee('25.0000%')->assertSee('75.0000%')->assertSee('gacha-reel');
    }
}
