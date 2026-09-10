<?php
namespace Tests\Feature;

use App\Models\{User, Category, Product, ServiceOrder, TopupTransaction, GameAccount};
use App\Notifications\StoreNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); config(['store.service_catalog_enabled'=>true]); }

    public function test_service_order_notifies_admin_once_and_customer_on_each_transition(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create(['balance'=>100]);
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Gift','is_active'=>true]);
        $variant = $product->variants()->create(['name'=>'Pack','price'=>10]);
        $body = ['variant_id'=>$variant->id,'quantity'=>1,'recipient'=>'private-player','request_id'=>(string)Str::uuid(),'accept_terms'=>1];
        $this->actingAs($user)->post(route('orders.store',$product),$body)->assertSessionHasNoErrors();
        $this->post(route('orders.store',$product),$body)->assertSessionHasNoErrors();
        $this->assertSame(1,$admin->notifications()->count());
        $this->assertSame(0,$user->notifications()->count());
        $order = ServiceOrder::firstOrFail();
        $this->actingAs($admin)->put(route('admin.service-orders.update',$order),['status'=>'processing','delivery_note'=>'Secret delivery details'])->assertSessionHasNoErrors();
        $this->put(route('admin.service-orders.update',$order),['status'=>'completed','delivery_note'=>'Secret delivery details'])->assertSessionHasNoErrors();
        $this->assertSame(2,$user->notifications()->count());
        $this->assertStringNotContainsString('Secret delivery',$user->notifications()->first()->data['message']);
        $this->put(route('admin.service-orders.update',$order),['status'=>'completed','delivery_note'=>'Duplicate'])->assertSessionHasErrors();
        $this->assertSame(2,$user->notifications()->count());
    }

    public function test_topup_approval_and_rejection_notify_once_and_link_to_exact_record(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create(['balance'=>0]);
        $this->actingAs($user)->post(route('wallet.topups.store'),['request_id'=>(string) \Illuminate\Support\Str::uuid(),'amount'=>100,'payment_method'=>'promptpay_slip'])->assertSessionHasNoErrors();
        $this->assertSame(1,$admin->notifications()->count());
        $topup = TopupTransaction::firstOrFail();
        $this->actingAs($admin)->post(route('admin.topups.approve',$topup), ['funds_received'=>1,'received_amount'=>$topup->amount,'transfer_reference'=>'TEST-100'])->assertSessionHasNoErrors();
        $this->post(route('admin.topups.reject',$topup))->assertSessionHasErrors();
        $this->assertSame(1,$user->notifications()->count());
        $this->assertSame('100.00',$user->fresh()->balance);
        $notification = $user->notifications()->first();
        $this->actingAs($user)->post(route('notifications.open',$notification->id))
            ->assertRedirect(route('wallet.index',['topup'=>$topup->id]).'#topup-'.$topup->id);
        $this->get(route('wallet.index',['topup'=>$topup->id]))->assertOk()->assertSee($topup->reference_no);
        $this->post(route('wallet.topups.store'),['request_id'=>(string) \Illuminate\Support\Str::uuid(),'amount'=>25,'payment_method'=>'truemoney_gift'])->assertSessionHasNoErrors();
        $second = TopupTransaction::latest('id')->first();
        $this->actingAs($admin)->post(route('admin.topups.reject',$second), ['reason'=>'ไม่พบยอดเข้าทดสอบ'])->assertSessionHasNoErrors();
        $this->assertSame(2,$user->notifications()->count());
        $this->assertSame('100.00',$user->fresh()->balance);
    }

    public function test_notification_access_and_mark_all_are_scoped_to_current_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $user->notify(new StoreNotification('My notice','Mine','unknown',1));
        $other->notify(new StoreNotification('Private notice','Other user','unknown',1));
        $this->getJson(route('notifications.count'))->assertUnauthorized();
        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('My notice')->assertDontSee('Private notice');
        $this->getJson(route('notifications.count'))->assertJson(['unread'=>1]);
        $this->post(route('notifications.open',$other->notifications()->first()->id))->assertNotFound();
        $this->post(route('notifications.read-all'))->assertSessionHasNoErrors();
        $this->assertSame(0,$user->unreadNotifications()->count());
        $this->assertSame(1,$other->unreadNotifications()->count());
        $this->get(route('notifications.index',['filter'=>'unread']))->assertDontSee('My notice');
    }

    public function test_shop_purchase_notifies_both_sides_without_credentials(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create(['balance'=>100]);
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $account = GameAccount::create(['category_id'=>$category->id,'title'=>'Game ID','price'=>20,'credentials_data'=>['password'=>'private-password'],'status'=>'available']);
        $this->actingAs($user)->post(route('accounts.buy',$account))->assertRedirect();
        $this->assertSame(1,$admin->notifications()->count());
        $this->assertSame(1,$user->notifications()->count());
        $this->get(route('notifications.index'))->assertDontSee('private-password');
        $this->post(route('accounts.buy',$account))->assertSessionHasErrors();
        $this->assertSame(1,$admin->notifications()->count());
    }

    public function test_refund_creates_customer_notification_and_notification_failure_rolls_back_approval(): void
    {
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create(['balance'=>100]);
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Gift','is_active'=>true]);
        $variant = $product->variants()->create(['name'=>'Pack','price'=>10]);
        $this->actingAs($user)->post(route('orders.store',$product),['variant_id'=>$variant->id,'quantity'=>1,'recipient'=>'Player','request_id'=>(string)Str::uuid(),'accept_terms'=>1]);
        $order = ServiceOrder::firstOrFail();
        $this->actingAs($admin)->put(route('admin.service-orders.update',$order),['status'=>'refunded','delivery_note'=>'Cannot deliver'])->assertSessionHasNoErrors();
        $this->assertSame('order',$user->notifications()->first()->data['target']);
        $this->assertStringContainsString('คืนเงิน',$user->notifications()->first()->data['title']);
        $topup = TopupTransaction::create(['user_id'=>$user->id,'amount'=>50,'payment_method'=>'promptpay_slip','reference_no'=>'rollback']);
        DatabaseNotification::creating(fn()=>throw new \RuntimeException('Cannot save notice'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('admin.topups.approve',$topup), ['funds_received'=>1,'received_amount'=>50,'transfer_reference'=>'TEST-50']);
            $this->fail('Expected notification failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Cannot save notice',$e->getMessage());
        } finally { DatabaseNotification::flushEventListeners(); }
        $this->assertSame('pending',$topup->fresh()->status);
        $this->assertSame('100.00',$user->fresh()->balance);
    }
}
