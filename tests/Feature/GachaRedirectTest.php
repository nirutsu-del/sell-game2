<?php

namespace Tests\Feature;

use App\Models\{Category, GachaBox, GachaItem, GameAccount, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GachaRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function setupBox(): GachaBox
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'balance' => 100]));
        $box = GachaBox::create(['name' => 'Redirect test', 'price_per_spin' => 20, 'is_active' => true]);
        // Reproduce an unrelated background request replacing the previous URL.
        $this->get(route('notifications.count'))->assertOk();
        return $box;
    }

    public function test_successful_admin_forms_have_explicit_destinations(): void
    {
        $box = $this->setupBox();
        $this->post(route('admin.gacha.items.add', $box), ['reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 100])
            ->assertRedirect(route('admin.gacha.items', $box))->assertSessionHas('success');
        $item = $box->items()->firstOrFail();
        $this->put(route('admin.gacha.items.rates', $box), ['rates' => [$item->id => 75]])
            ->assertRedirect(route('admin.gacha.items', $box))->assertSessionHas('success');
        $this->assertSame(75.0, (float) $item->fresh()->drop_rate);
        $this->put(route('admin.gacha.update', $box), ['name' => 'Updated box', 'price_per_spin' => 10])
            ->assertRedirect(route('admin.gacha.edit', $box))->assertSessionHas('success');
        $this->post(route('admin.gacha.store'), ['name' => 'New box', 'price_per_spin' => 10])
            ->assertRedirect(route('admin.gacha.items', GachaBox::latest('id')->first()));
    }

    public function test_invalid_forms_return_to_their_own_pages_with_old_input(): void
    {
        $box = $this->setupBox();
        foreach ([
            ['post', 'admin.gacha.store', [], 'admin.gacha.create', [], ['name' => 'Draft', 'price_per_spin' => -1], 'price_per_spin'],
            ['put', 'admin.gacha.update', [$box], 'admin.gacha.edit', [$box], ['name' => 'Draft', 'price_per_spin' => -1], 'price_per_spin'],
            ['post', 'admin.gacha.items.add', [$box], 'admin.gacha.items', [$box], ['reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 101], 'drop_rate'],
            ['put', 'admin.gacha.items.rates', [$box], 'admin.gacha.items', [$box], ['rates' => [1 => 101]], 'rates.1'],
            ['post', 'gacha.spin', [$box], 'gacha.show', [$box], ['request_id' => 'invalid'], 'request_id'],
        ] as [$method, $route, $params, $destination, $destinationParams, $body, $error]) {
            $this->{$method}(route($route, $params), $body)
                ->assertRedirect(route($destination, $destinationParams))->assertSessionHasErrors($error);
            foreach ($body as $key => $value) {
                $this->assertEquals($value, session()->getOldInput($key));
            }
        }
        $this->postJson(route('gacha.spin', $box), ['request_id' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors('request_id');
    }

    public function test_unavailable_account_returns_to_reward_page(): void
    {
        $box = $this->setupBox();
        $category = Category::create(['name' => 'Game', 'slug' => 'game']);
        $account = GameAccount::create(['category_id' => $category->id, 'title' => 'Sold', 'price' => 50, 'status' => 'sold', 'credentials_data' => []]);
        $this->post(route('admin.gacha.items.add', $box), ['reward_type' => 'game_account', 'game_account_id' => $account->id, 'drop_rate' => 10])
            ->assertRedirect(route('admin.gacha.items', $box))->assertSessionHasErrors('game_account_id');
        $this->assertDatabaseCount('gacha_items', 0);
    }

    public function test_html_spin_success_and_business_errors_stay_on_box_page(): void
    {
        $box = $this->setupBox();
        GachaItem::create(['gacha_box_id' => $box->id, 'reward_type' => 'credit', 'credit_amount' => 5, 'drop_rate' => 100]);
        $this->post(route('gacha.spin', $box))->assertRedirect(route('gacha.show', $box))->assertSessionHas('success');
        $box->update(['is_active' => false]);
        $this->post(route('gacha.spin', $box))->assertRedirect(route('gacha.show', $box))->assertSessionHasErrors('box');
    }

    public function test_ajax_notification_poll_does_not_replace_previous_page(): void
    {
        $box = $this->setupBox();
        $this->get(route('admin.gacha.items', $box))->assertOk();
        $this->getJson(route('notifications.count'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame(route('admin.gacha.items', $box), session()->previousUrl());
    }
}
