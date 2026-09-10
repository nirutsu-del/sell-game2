<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\GameAccount;
use App\Models\TopupTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'เกมทดสอบ', 'slug' => 'test-game']);

        $response = $this->actingAs($admin)->post(route('admin.accounts.store'), [
            'category_id' => $category->id,
            'title' => 'ไอดีทดสอบ',
            'description' => 'พร้อมขาย',
            'price' => 199,
            'username' => 'player',
            'password_value' => 'secret',
        ]);

        $response->assertRedirect(route('admin.accounts.index'));
        $this->actingAs($admin)->get(route('admin.accounts.index'))->assertOk()->assertSee('ไอดีทดสอบ');
        $account = GameAccount::firstOrFail();
        $this->assertSame('ไอดีทดสอบ', $account->title);
    }

    public function test_admin_can_approve_a_pending_topup_once(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['balance' => 0]);
        $topup = TopupTransaction::create([
            'user_id' => $customer->id,
            'amount' => 150,
            'payment_method' => 'promptpay_slip',
            'reference_no' => 'TOPUP-TEST-001',
        ]);

        $this->actingAs($admin)->post(route('admin.topups.approve', $topup), ['funds_received'=>1,'received_amount'=>150,'transfer_reference'=>'TEST-150'])->assertSessionHas('success');

        $this->assertSame('success', $topup->fresh()->status);
        $this->assertSame('150.00', $customer->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 1);
    }
}
