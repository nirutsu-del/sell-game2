<?php

namespace Tests\Feature;

use App\Models\{TopupTransaction, TopupReview, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TopupTransferReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function topup(string $method = 'promptpay_slip'): TopupTransaction
    {
        return TopupTransaction::create(['user_id'=>User::factory()->create(['balance'=>0])->id,
            'amount'=>100, 'payment_method'=>$method, 'reference_no'=>fake()->uuid()]);
    }

    private function approve(TopupTransaction $topup, string $reference = 'BANK-123')
    {
        return $this->post(route('admin.topups.approve', $topup), [
            'funds_received'=>1, 'received_amount'=>100, 'transfer_reference'=>$reference,
        ]);
    }

    public function test_reference_is_unique_across_buyers_but_scoped_to_payment_channel(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        $first = $this->topup(); $second = $this->topup(); $otherChannel = $this->topup('truemoney_gift');
        $this->approve($first)->assertStatus(303);
        $this->approve($second, ' bank-123 ')->assertSessionHasErrors('transfer_reference');
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertSame('0.00', $second->user->fresh()->balance);
        $this->assertSame(0, $second->user->notifications()->count());
        $this->assertDatabaseCount('wallet_transactions', 1);
        $this->assertDatabaseCount('topup_reviews', 1);
        $this->approve($otherChannel)->assertStatus(303);
        $this->approve($second, 'BANK-456')->assertStatus(303);
        $this->assertDatabaseCount('topup_transfer_references', 3);
    }

    public function test_failed_approval_releases_reference_for_retry(): void
    {
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        $topup = $this->topup();
        TopupReview::creating(fn()=>throw new \RuntimeException('audit failure'));
        $this->withoutExceptionHandling();
        try {
            $this->approve($topup);
            $this->fail('Expected failure');
        } catch (\RuntimeException $error) {
            $this->assertSame('audit failure', $error->getMessage());
        } finally { TopupReview::flushEventListeners(); }
        $this->assertDatabaseCount('topup_transfer_references', 0);
        $this->assertDatabaseCount('wallet_transactions', 0);
        $this->assertSame('0.00', $topup->user->fresh()->balance);
        $this->approve($topup)->assertStatus(303);
    }

    public function test_migration_reserves_historical_references_without_changing_duplicate_history(): void
    {
        $first = $this->topup(); $duplicate = $this->topup(); $pending = $this->topup();
        foreach ([$first, $duplicate] as $topup) {
            $topup->update(['status'=>'success','verification_payload'=>['transfer_reference'=>' old-ref ']]);
        }
        $migration = require database_path('migrations/2026_09_17_000001_create_topup_transfer_references.php');
        $migration->down(); $migration->up();
        $this->assertDatabaseCount('topup_transfer_references', 1);
        $this->assertSame(2, TopupTransaction::where('status', 'success')->count());
        $this->assertEquals($first->id, DB::table('topup_transfer_references')->value('topup_id'));
        $this->actingAs(User::factory()->create(['role'=>'admin']));
        $this->approve($pending, 'OLD-REF')->assertSessionHasErrors('transfer_reference');
        $this->assertDatabaseCount('wallet_transactions', 0);
    }
}
