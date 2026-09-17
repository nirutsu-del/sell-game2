<?php
namespace Tests\Feature;

use App\Models\{User, TopupTransaction};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class TopupIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_creates_one_record_file_and_notification_even_after_approval(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create();
        $body = ['request_id'=>(string) Str::uuid(), 'amount'=>'300.00', 'payment_method'=>'promptpay_slip'];
        $send = function () use ($body) {
            $body['slip'] = UploadedFile::fake()->createWithContent('slip.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));
            return $this->post(route('wallet.topups.store'), $body);
        };
        $this->actingAs($user);
        $send()->assertStatus(303);
        $topup = TopupTransaction::firstOrFail();
        $send()->assertRedirect(route('wallet.index', ['topup'=>$topup->id]));
        $this->assertDatabaseCount('topup_transactions', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles('slips'));
        $this->assertSame(1, $admin->notifications()->count());
        $this->actingAs($admin)->post(route('admin.topups.approve', $topup), ['funds_received'=>1,'received_amount'=>300,'transfer_reference'=>'TEST-300']);
        $this->actingAs($user);
        $send()->assertStatus(303);
        $this->assertSame('300.00', $user->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->get(route('wallet.index', ['topup'=>$topup->id]))->assertOk()->assertSee($topup->reference_no)->assertSee('สำเร็จ')->assertDontSee('id="topup-form"', false);
    }

    public function test_conflicting_payload_is_rejected_and_other_users_cannot_read_receipt(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $body = ['request_id'=>(string) Str::uuid(), 'amount'=>100, 'payment_method'=>'promptpay_slip', 'slip'=>$this->paymentSlip()];
        $this->actingAs($user)->post(route('wallet.topups.store'), $body)->assertStatus(303);
        $body['amount'] = 200;
        $this->post(route('wallet.topups.store'), $body)->assertSessionHasErrors('request_id');
        $this->assertDatabaseCount('topup_transactions', 1);
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('wallet.index',['topup'=>TopupTransaction::first()->id]))->assertNotFound();
        $body['slip'] = $this->paymentSlip();
        $this->post(route('wallet.topups.store'), $body)->assertStatus(303);
        $this->assertDatabaseCount('topup_transactions', 2);
    }

    public function test_request_key_is_required_and_amount_has_at_most_two_decimals(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('wallet.topups.store'), ['amount'=>100,'payment_method'=>'promptpay_slip'])->assertSessionHasErrors('request_id');
        $this->post(route('wallet.topups.store'), ['request_id'=>(string) Str::uuid(),'amount'=>'1.001','payment_method'=>'promptpay_slip'])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('topup_transactions', 0);
    }
}
