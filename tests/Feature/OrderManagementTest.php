<?php
namespace Tests\Feature;

use App\Models\{Category, Product, ServiceOrder, User, GameAccount, PurchaseHistory, GachaBox, GachaItem, GachaSpin, OrderStatusEvent};
use App\Services\ServiceOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); config(['store.service_catalog_enabled'=>true]); }

    private function order(User $user, string $name = 'Service pack'): ServiceOrder
    {
        $category = Category::firstOrCreate(['slug'=>'test'],['name'=>'Test']);
        $product = Product::create(['category_id'=>$category->id,'name'=>$name,'is_active'=>true]);
        $variant = $product->variants()->create(['name'=>'Small','price'=>10,'stock'=>10]);
        return app(ServiceOrderService::class)->place($user,$product,[
            'variant_id'=>$variant->id,'quantity'=>1,'recipient'=>'Player','request_id'=>(string)Str::uuid(),
        ]);
    }

    private function purchase(User $user, string $source): PurchaseHistory
    {
        $category = Category::firstOrCreate(['slug'=>'test'],['name'=>'Test']);
        $account = GameAccount::create(['category_id'=>$category->id,'title'=>'Account '.$source,'price'=>20,'credentials_data'=>['username'=>'secret-user','password'=>'never-render-me'],'status'=>'sold']);
        return PurchaseHistory::create(['user_id'=>$user->id,'game_account_id'=>$account->id,'price_paid'=>20,'source'=>$source,'account_data_delivered'=>encrypt('secret')]);
    }

    public function test_combined_history_filters_and_does_not_duplicate_gacha_or_leak_other_users(): void
    {
        $user = User::factory()->create(['balance'=>1000]);
        $service = $this->order($user);
        $purchase = $this->purchase($user,'shop');
        $reward = $this->purchase($user,'gacha');
        $box = GachaBox::create(['name'=>'Mystery box','price_per_spin'=>10,'is_active'=>true]);
        $item = GachaItem::create(['gacha_box_id'=>$box->id,'reward_type'=>'game_account','game_account_id'=>$reward->game_account_id,'drop_rate'=>100]);
        $spin = GachaSpin::create(['user_id'=>$user->id,'gacha_box_id'=>$box->id,'gacha_item_id'=>$item->id,'purchase_history_id'=>$reward->id,'price_paid'=>10]);
        $other = User::factory()->create(['balance'=>1000]);
        $this->order($other,'Private service');
        $response = $this->actingAs($user)->get(route('orders.index'));
        $response->assertOk()->assertDontSee('Private service')->assertDontSee('never-render-me');
        $response->assertViewHas('orders',fn($page)=>$page->total() === 3);
        $this->get(route('orders.index',['type'=>'account']))->assertViewHas('orders',fn($p)=>$p->total() === 1);
        $this->get(route('orders.index',['status'=>'pending']))->assertViewHas('orders',fn($p)=>$p->total() === 1);
        $this->get(route('orders.index',['q'=>'G-'.$spin->id]))->assertViewHas('orders',fn($p)=>$p->total() === 1 && $p->first()['reference'] === 'G-'.$spin->id);
        $this->get(route('orders.index',['q'=>'S-'.$service->id]))->assertViewHas('orders',fn($p)=>$p->total() === 1 && $p->first()['reference'] === 'S-'.$service->id);
        $this->get(route('orders.index',['q'=>'A-'.$purchase->id]))->assertViewHas('orders',fn($p)=>$p->total() === 1);
        $this->get(route('orders.index',['q'=>'Mystery']))->assertViewHas('orders',fn($p)=>$p->total() === 1);
    }

    public function test_history_paginates_and_keeps_filters(): void
    {
        $user = User::factory()->create(['balance'=>1000]);
        for ($i=0;$i<16;$i++) $this->order($user);
        $this->actingAs($user)->get(route('orders.index',['type'=>'service','page'=>2]))
            ->assertOk()->assertViewHas('orders',fn($p)=>$p->total() === 16 && $p->count() === 1 && str_contains($p->url(1),'type=service'));
    }

    public function test_queue_filters_old_open_work_and_rejects_non_admins(): void
    {
        $user = User::factory()->create(['balance'=>1000]);
        $old = $this->order($user,'Old work'); $old->update(['created_at'=>now()->subHours(30)]);
        $this->order($user,'New work');
        $done = $this->order($user,'Done work'); $done->update(['status'=>'completed','created_at'=>now()->subDays(3)]);
        $this->actingAs($user)->get(route('admin.service-orders.index'))->assertForbidden();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->get(route('admin.service-orders.index'))->assertOk()->assertViewHas('orders',fn($p)=>$p->total() === 2 && $p->first()->id === $old->id);
        $this->get(route('admin.service-orders.index',['status'=>'all','waiting'=>1]))->assertViewHas('orders',fn($p)=>$p->total() === 1 && $p->first()->id === $old->id);
        $this->get(route('admin.service-orders.index',['status'=>'completed']))->assertViewHas('orders',fn($p)=>$p->total() === 1);
    }

    public function test_actor_and_transitions_are_recorded_and_stale_update_does_not_refund(): void
    {
        $user = User::factory()->create(['balance'=>100]);
        $order = $this->order($user);
        $admin = User::factory()->create(['role'=>'admin','name'=>'Queue operator']);
        $url = route('admin.service-orders.update',$order);
        $this->actingAs($admin)->put($url,['status'=>'processing','delivery_note'=>'Working','expected_status'=>'pending'])->assertSessionHasNoErrors();
        $this->put($url,['status'=>'refunded','delivery_note'=>'Old form','expected_status'=>'pending'])->assertSessionHasErrors('status');
        $this->assertSame('90.00',$user->fresh()->balance);
        $this->assertDatabaseCount('order_status_events',1);
        $this->put($url,['status'=>'refunded','delivery_note'=>'Cannot deliver','expected_status'=>'processing'])->assertSessionHasNoErrors();
        $this->assertSame('100.00',$user->fresh()->balance);
        $this->assertDatabaseHas('order_status_events',['service_order_id'=>$order->id,'actor_id'=>$admin->id,'actor_name'=>'Queue operator','from_status'=>'processing','to_status'=>'refunded','note'=>'Cannot deliver']);
        $this->actingAs($user)->get(route('orders.show',$order))->assertOk()->assertSee('Cannot deliver')->assertDontSee('Queue operator');
    }

    public function test_refund_and_status_roll_back_if_audit_record_cannot_be_saved(): void
    {
        $user = User::factory()->create(['balance'=>100]);
        $order = $this->order($user);
        $admin = User::factory()->create(['role'=>'admin']);
        OrderStatusEvent::creating(fn()=>throw new \RuntimeException('Audit unavailable'));
        try {
            app(ServiceOrderService::class)->updateStatus($order,'refunded','Refund',$admin);
            $this->fail('Expected audit failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Audit unavailable',$e->getMessage());
        } finally {
            OrderStatusEvent::flushEventListeners();
        }
        $this->assertSame('90.00',$user->fresh()->balance);
        $this->assertSame('pending',$order->fresh()->status);
        $this->assertDatabaseCount('wallet_transactions',1);
    }
}
