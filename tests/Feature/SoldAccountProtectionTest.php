<?php

namespace Tests\Feature;

use App\Models\{Category, GameAccount, PurchaseHistory, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoldAccountProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function account(bool $sold = true): GameAccount
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'sold-test']);
        $account = GameAccount::create([
            'category_id' => $category->id, 'title' => 'Original', 'price' => 100,
            'status' => $sold ? 'sold' : 'available', 'images' => ['game-accounts/old.png'],
            'credentials_data' => ['username' => 'player', 'password' => 'secret'],
        ]);
        if ($sold) {
            PurchaseHistory::create([
                'user_id' => User::factory()->create()->id, 'game_account_id' => $account->id,
                'price_paid' => 100, 'account_data_delivered' => encrypt('test'), 'source' => 'shop',
            ]);
        }
        return $account;
    }

    private function editData(GameAccount $account): array
    {
        return ['category_id' => $account->category_id, 'title' => 'Edited', 'price' => 150, 'status' => 'available'];
    }

    public function test_reopening_sold_account_preserves_fields_credentials_and_images(): void
    {
        Storage::fake('public');
        $account = $this->account();
        Storage::disk('public')->put('game-accounts/old.png', 'original image');
        $before = $account->fresh()->getRawOriginal();
        $upload = UploadedFile::fake()->createWithContent('new.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->from(route('admin.accounts.edit', $account))
            ->put(route('admin.accounts.update', $account), $this->editData($account) + [
                'images' => [$upload], 'password_value' => 'changed-secret',
            ])->assertRedirect(route('admin.accounts.edit', $account))->assertSessionHasErrors('status');
        $this->assertSame($before, $account->fresh()->getRawOriginal());
        $this->assertSame(['game-accounts/old.png'], Storage::disk('public')->allFiles());
        $this->assertSame('original image', Storage::disk('public')->get('game-accounts/old.png'));
    }

    public function test_unsold_account_can_still_be_edited(): void
    {
        $account = $this->account(false);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->put(route('admin.accounts.update', $account), $this->editData($account))
            ->assertRedirect(route('admin.accounts.index'))->assertSessionHasNoErrors();
        $this->assertSame('Edited', $account->fresh()->title);
        $this->assertSame('150.00', $account->fresh()->price);
    }

    public function test_checkout_rejects_previously_sold_account_with_incorrect_available_status(): void
    {
        $account = $this->account();
        $account->update(['status' => 'available']);
        $buyer = User::factory()->create(['balance' => 500]);
        $this->actingAs($buyer)->postJson(route('accounts.buy', $account))
            ->assertUnprocessable()->assertJsonValidationErrors('account');
        $this->assertSame('500.00', $buyer->fresh()->balance);
        $this->assertSame('available', $account->fresh()->status);
        $this->assertDatabaseCount('purchase_histories', 1);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_failed_update_keeps_original_image_and_removes_staged_upload(): void
    {
        Storage::fake('public');
        $account = $this->account(false);
        Storage::disk('public')->put('game-accounts/old.png', 'original image');
        $upload = UploadedFile::fake()->createWithContent('new.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
        GameAccount::saving(function () { throw new \RuntimeException('Simulated database failure'); });
        try {
            $this->withoutExceptionHandling()->actingAs(User::factory()->create(['role' => 'admin']))
                ->put(route('admin.accounts.update', $account), $this->editData($account) + ['images' => [$upload]]);
            $this->fail('The simulated failure must be thrown.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            \Illuminate\Support\Facades\Event::forget('eloquent.saving: '.GameAccount::class);
        }
        $this->assertSame('Original', $account->fresh()->title);
        $this->assertSame(['game-accounts/old.png'], $account->fresh()->images);
        $this->assertSame(['game-accounts/old.png'], Storage::disk('public')->allFiles());
    }
}
