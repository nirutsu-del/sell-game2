<?php
namespace Tests\Feature;
use App\Models\{TopupTransaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, DB};
use Illuminate\Support\Str;
use Tests\TestCase;

class SlipReviewTest extends TestCase {
    use RefreshDatabase;
    public function test_both_channels_require_slip_without_creating_pending_records(): void {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        foreach (['promptpay_slip','truemoney_gift'] as $method) {
            $this->post(route('wallet.topups.store'), ['request_id'=>(string)Str::uuid(),'amount'=>100,'payment_method'=>$method])
                ->assertSessionHasErrors('slip');
        }
        $this->assertDatabaseCount('topup_transactions',0);
        $this->assertDatabaseCount('notifications',0);
        $this->assertDatabaseCount('topup_slip_hashes',0);
        $this->assertCount(0,Storage::disk('local')->allFiles('slips'));
    }
    private function body(): array {
        return ['request_id'=>(string)Str::uuid(),'amount'=>100,'payment_method'=>'promptpay_slip',
            'slip'=>UploadedFile::fake()->createWithContent('test.png',base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='))];
    }
    public function test_identical_file_is_rejected_for_new_request_and_other_user(): void {
        Storage::fake('local');
        $this->actingAs(User::factory()->create())->post(route('wallet.topups.store'),$this->body())->assertStatus(303);
        $this->post(route('wallet.topups.store'),$this->body())->assertSessionHasErrors('slip');
        $this->actingAs(User::factory()->create())->post(route('wallet.topups.store'),$this->body())->assertSessionHasErrors('slip');
        $this->assertDatabaseCount('topup_transactions',1);
        $this->assertDatabaseCount('topup_slip_hashes',1);
        $this->assertCount(1,Storage::disk('local')->allFiles('slips'));
    }
    public function test_historical_slips_are_indexed_idempotently(): void {
        Storage::fake('local');
        $this->actingAs(User::factory()->create())->post(route('wallet.topups.store'),$this->body());
        DB::table('topup_slip_hashes')->delete();
        $this->artisan('store:index-slips')->assertExitCode(0);
        $this->artisan('store:index-slips')->assertExitCode(0);
        $this->assertDatabaseCount('topup_slip_hashes',1);
        $this->post(route('wallet.topups.store'),$this->body())->assertSessionHasErrors('slip');
    }
    public function test_manual_approval_requires_confirmation_matching_amount_and_reference(): void {
        $buyer = User::factory()->create(['balance'=>0]);
        $admin = User::factory()->create(['role'=>'admin']);
        $topup = TopupTransaction::create(['user_id'=>$buyer->id,'amount'=>100,'payment_method'=>'promptpay_slip','reference_no'=>'REVIEW']);
        $this->actingAs($admin)->post(route('admin.topups.approve',$topup))->assertSessionHasErrors(['funds_received','received_amount','transfer_reference']);
        $this->post(route('admin.topups.approve',$topup),['funds_received'=>1,'received_amount'=>99,'transfer_reference'=>'TEST'])->assertSessionHasErrors('received_amount');
        $this->assertSame('0.00',$buyer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions',0);
        $this->post(route('admin.topups.approve',$topup),['funds_received'=>1,'received_amount'=>100,'transfer_reference'=>'TEST'])->assertStatus(303);
        $this->assertSame('100.00',$buyer->fresh()->balance);
        $this->assertSame($admin->id,$topup->fresh()->verification_payload['reviewer_id']);
        $this->get(route('admin.topups.index'))->assertOk()->assertSee('TEST');
    }
}
