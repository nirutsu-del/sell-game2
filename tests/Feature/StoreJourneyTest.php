<?php

namespace Tests\Feature;

use App\Models\{Category, GameAccount, PurchaseHistory, TopupTransaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Mail, Storage};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreJourneyTest extends TestCase
{
    use RefreshDatabase;

    private function slip(): UploadedFile
    {
        // A tiny PNG fixture; does not require GD or contain a real payment slip.
        return UploadedFile::fake()->createWithContent('test-slip.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }

    public static function paymentMethods(): array
    {
        return [['promptpay_slip'], ['truemoney_gift']];
    }

    #[DataProvider('paymentMethods')]
    public function test_registration_topup_approval_purchase_and_private_delivery(string $method): void
    {
        Storage::fake('local');
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'password' => 'admin-test-password']);
        $category = Category::create(['name' => 'เกมทดสอบ', 'slug' => 'journey-test']);
        $account = GameAccount::create([
            'category_id' => $category->id, 'title' => 'Journey test account',
            'price' => 199, 'status' => 'available',
            'credentials_data' => ['username' => 'test-player-only', 'password' => 'test-delivery-secret'],
        ]);

        $this->get(route('accounts.show', $account))->assertOk()->assertDontSee('test-delivery-secret');
        $this->post(route('register'), [
            'name' => 'Test buyer', 'email' => 'journey@example.test',
            'password' => 'buyer-test-password', 'password_confirmation' => 'buyer-test-password',
            'role' => 'admin', 'balance' => 999999,
        ])->assertRedirect(route('user.dashboard'));
        $buyer = User::where('email', 'journey@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($buyer);
        $this->assertFalse($buyer->isAdmin());
        $this->assertSame('0.00', $buyer->balance);

        $this->get(route('wallet.index'))->assertOk();
        $this->post(route('accounts.buy', $account))->assertSessionHasErrors('balance');
        $this->assertDatabaseCount('purchase_histories', 0);
        $this->post(route('wallet.topups.store'), [
            'request_id' => (string) \Illuminate\Support\Str::uuid(),
            'amount' => 300, 'payment_method' => $method,
            'slip' => $this->slip(),
        ])->assertSessionHas('success');
        $topup = TopupTransaction::where('user_id', $buyer->id)->firstOrFail();
        Storage::disk('local')->assertExists($topup->slip_path);
        $this->assertSame('pending', $topup->status);
        $this->assertSame('0.00', $buyer->fresh()->balance);
        $this->post(route('admin.topups.approve', $topup))->assertForbidden();
        $this->get(route('admin.topups.slip', $topup))->assertForbidden();

        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login.store'), ['email' => $admin->email, 'password' => 'admin-test-password'])->assertRedirect();
        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.topups.index'))->assertOk()->assertSee($topup->reference_no);
        $this->get(route('admin.topups.slip', $topup))->assertOk();
        $this->post(route('admin.topups.approve', $topup), ['funds_received'=>1,'received_amount'=>300,'transfer_reference'=>'TEST-300'])->assertSessionHas('success');
        $this->post(route('admin.topups.approve', $topup))->assertSessionHasErrors('topup');
        $this->assertSame('300.00', $buyer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);

        $this->post(route('logout'))->assertRedirect();
        $this->post(route('login.store'), ['email' => $buyer->email, 'password' => 'buyer-test-password'])->assertRedirect();
        $this->assertAuthenticatedAs($buyer);
        $response = $this->post(route('accounts.buy', $account));
        $purchase = PurchaseHistory::where('user_id', $buyer->id)->firstOrFail();
        $response->assertRedirect(route('purchases.show', $purchase));
        $this->get(route('purchases.show', $purchase))->assertOk()
            ->assertSee('test-player-only')->assertSee('test-delivery-secret');
        $this->assertStringNotContainsString('test-delivery-secret', $purchase->getRawOriginal('account_data_delivered'));
        $this->assertSame('101.00', $buyer->fresh()->balance);
        $this->assertSame('sold', $account->fresh()->status);
        $this->post(route('accounts.buy', $account))->assertSessionHasErrors('account');
        $this->assertDatabaseCount('purchase_histories', 1);
        $this->assertDatabaseCount('wallet_transactions', 2);
        $this->assertSame('101.00', $buyer->fresh()->balance);
        $this->get(route('notifications.index'))->assertOk()->assertSee('ส่งมอบไอดีแล้ว');

        $this->post(route('logout'))->assertRedirect();
        $this->get(route('purchases.show', $purchase))->assertRedirect(route('login'));
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('purchases.show', $purchase))->assertNotFound();
        Mail::assertNothingSent();
    }

    public function test_rejected_topup_does_not_credit_wallet_or_allow_purchase(): void
    {
        Storage::fake('local');
        $buyer = User::factory()->create(['balance' => 0]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($buyer)->post(route('wallet.topups.store'), [
            'request_id' => (string) \Illuminate\Support\Str::uuid(),
            'amount' => 300, 'payment_method' => 'promptpay_slip',
            'slip' => $this->slip(),
        ])->assertSessionHas('success');
        $topup = TopupTransaction::firstOrFail();
        $this->actingAs($admin)->post(route('admin.topups.reject', $topup), ['reason'=>'ไม่พบยอดเข้าทดสอบ'])->assertSessionHas('success');
        $this->post(route('admin.topups.approve', $topup))->assertSessionHasErrors('topup');
        $this->assertSame('failed', $topup->fresh()->status);
        $this->assertSame('0.00', $buyer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $category = Category::create(['name' => 'Rejected test', 'slug' => 'rejected-test']);
        $account = GameAccount::create([
            'category_id' => $category->id, 'title' => 'Not paid', 'price' => 199,
            'status' => 'available', 'credentials_data' => ['username' => 'dummy'],
        ]);
        $this->actingAs($buyer)->post(route('accounts.buy', $account))->assertSessionHasErrors('balance');
        $this->assertSame('available', $account->fresh()->status);
        $this->assertDatabaseCount('purchase_histories', 0);
    }
}
