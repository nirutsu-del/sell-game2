<?php

namespace Tests\Feature;

use App\Models\{Category, GameAccount, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_be_created_without_optional_slug(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.categories.store'), ['name' => 'Optional slug'])
            ->assertRedirect(route('admin.categories.index'))->assertSessionHasNoErrors();
        $this->assertNotEmpty(Category::firstOrFail()->slug);
    }

    public function test_zero_maximum_price_only_returns_free_accounts(): void
    {
        $category = Category::create(['name' => 'Game', 'slug' => 'edge-game']);
        foreach ([0, 100] as $price) {
            GameAccount::create(['category_id' => $category->id, 'title' => 'Account '.$price,
                'price' => $price, 'status' => 'available', 'credentials_data' => ['username' => 'player', 'password' => 'secret']]);
        }
        $this->get(route('accounts.index', ['max_price' => '0']))->assertOk()
            ->assertViewHas('accounts', fn ($accounts) => $accounts->total() === 1 && $accounts->first()->price === '0.00');
    }

    public function test_invalid_account_filters_are_rejected(): void
    {
        foreach (['category', 'min_price', 'max_price'] as $field) {
            $this->getJson(route('accounts.index', [$field => ['invalid']]))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_login_rejects_array_password_without_server_error(): void
    {
        $user = User::factory()->create();
        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => ['invalid']])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertGuest();
    }
}