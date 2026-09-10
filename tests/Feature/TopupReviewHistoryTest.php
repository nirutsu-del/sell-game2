<?php
namespace Tests\Feature;
use App\Models\{TopupReview, TopupTransaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopupReviewHistoryTest extends TestCase {
    use RefreshDatabase;
    private function topup(User $user, string $reference): TopupTransaction {
        return TopupTransaction::create(['user_id'=>$user->id,'amount'=>100,'payment_method'=>'promptpay_slip','reference_no'=>$reference]);
    }
    public function test_reason_is_required_and_shown_only_to_owner_and_admin(): void {
        $buyer = User::factory()->create(['balance'=>0]);
        $admin = User::factory()->create(['role'=>'admin','name'=>'Reviewer Test']);
        $topup = $this->topup($buyer,'REJECT-HISTORY');
        $this->actingAs($admin)->post(route('admin.topups.reject',$topup))->assertSessionHasErrors('reason');
        $this->post(route('admin.topups.reject',$topup),['reason'=>str_repeat('x',501)])->assertSessionHasErrors('reason');
        $this->assertSame('pending',$topup->fresh()->status);
        $reason = 'ไม่พบยอดเข้าบัญชี <script>alert(1)</script>';
        $this->post(route('admin.topups.reject',$topup),['reason'=>$reason])->assertStatus(303);
        $this->post(route('admin.topups.reject',$topup),['reason'=>$reason])->assertSessionHasErrors('topup');
        $this->assertDatabaseCount('topup_reviews',1);
        $this->assertSame('0.00',$buyer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions',0);
        $this->get(route('admin.topups.history'))->assertOk()->assertSee('Reviewer Test')->assertSee($reason)->assertDontSee('<script>alert(1)</script>',false);
        $this->actingAs($buyer)->get(route('wallet.index',['topup'=>$topup->id]))->assertOk()->assertSee($reason)->assertDontSee('Reviewer Test');
        $this->assertStringContainsString($reason,$buyer->notifications()->first()->data['message']);
        $this->get(route('admin.topups.history'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('wallet.index',['topup'=>$topup->id]))->assertNotFound();
    }
    public function test_approval_records_one_event_and_filters_work(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        $topup = $this->topup(User::factory()->create(),'APPROVE-HISTORY');
        $body = ['funds_received'=>1,'received_amount'=>100,'transfer_reference'=>'TEST-REF'];
        $this->actingAs($admin)->post(route('admin.topups.approve',$topup),$body)->assertStatus(303);
        $this->post(route('admin.topups.approve',$topup),$body)->assertSessionHasErrors('topup');
        $this->assertDatabaseCount('topup_reviews',1);
        $this->assertDatabaseHas('topup_reviews',['admin_id'=>$admin->id,'action'=>'approved','from_status'=>'pending','to_status'=>'success']);
        $this->get(route('admin.topups.history',['action'=>'approved','reference'=>'APPROVE-HISTORY']))->assertOk()->assertSee('APPROVE-HISTORY');
        $this->get(route('admin.topups.history',['action'=>'rejected']))->assertOk()->assertSee('ยังไม่มีประวัติที่ตรงกับเงื่อนไข');
    }
    public function test_failed_history_write_rolls_back_credit_and_status(): void {
        $buyer = User::factory()->create(['balance'=>0]);
        $topup = $this->topup($buyer,'ROLLBACK-REVIEW');
        TopupReview::creating(fn()=>throw new \RuntimeException('History unavailable'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs(User::factory()->create(['role'=>'admin']))->post(route('admin.topups.approve',$topup),['funds_received'=>1,'received_amount'=>100,'transfer_reference'=>'TEST']);
            $this->fail('Expected history failure');
        } catch (\RuntimeException $error) {
            $this->assertSame('History unavailable',$error->getMessage());
        } finally { TopupReview::flushEventListeners(); }
        $this->assertSame('pending',$topup->fresh()->status);
        $this->assertSame('0.00',$buyer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions',0);
        $this->assertDatabaseCount('topup_reviews',0);
    }
}
