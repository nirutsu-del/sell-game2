<?php
namespace Tests\Feature;
use App\Models\{Category, Product, ProductVariant, ServiceOrder, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class CatalogTest extends TestCase {
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); config(['store.service_catalog_enabled'=>true]); }
    private function fixture(): array {
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Gift service','is_active'=>true]);
        $variant = $product->variants()->create(['name'=>'Pack 1','price'=>25,'stock'=>3]);
        return [$category,$product,$variant];
    }
    public function test_catalog_includes_nested_categories_and_active_products(): void {
        [$category,$product] = $this->fixture();
        $child = Category::create(['name'=>'Subgame','slug'=>'sub','parent_id'=>$category->id]);
        $product->update(['category_id'=>$child->id]);
        $this->get('/')->assertOk()->assertSee('Gift service');
        $this->get(route('catalog.category',$category))->assertOk()->assertSee('Gift service');
        $this->get(route('products.show',$product))->assertOk()->assertSee('Pack 1');
        $product->update(['is_active'=>false]);
        $this->get(route('products.show',$product))->assertNotFound();
        $this->get(route('catalog.index'))->assertDontSee('Gift service');
    }
    public function test_order_retry_charges_once_and_refund_restores_stock_once(): void {
        [, $product,$variant] = $this->fixture();
        $user = User::factory()->create(['balance'=>100]);
        $body = ['variant_id'=>$variant->id,'quantity'=>2,'recipient'=>'Player1','request_id'=>(string)Str::uuid(),'accept_terms'=>1];
        $this->actingAs($user)->post(route('orders.store',$product),$body)->assertRedirect();
        $this->post(route('orders.store',$product),$body)->assertRedirect();
        $this->assertSame('50.00',$user->fresh()->balance); $this->assertSame(1,$variant->fresh()->stock);
        $this->assertDatabaseCount('service_orders',1); $this->assertDatabaseCount('wallet_transactions',1);
        $order = ServiceOrder::firstOrFail();
        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get(route('orders.show',$order))->assertNotFound();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->put(route('admin.service-orders.update',$order),['status'=>'refunded','delivery_note'=>'Cancel'])->assertSessionHasNoErrors();
        $this->put(route('admin.service-orders.update',$order),['status'=>'refunded','delivery_note'=>'Again'])->assertSessionHasErrors();
        $this->assertSame('100.00',$user->fresh()->balance); $this->assertSame(3,$variant->fresh()->stock);
        $this->assertDatabaseCount('wallet_transactions',2);
    }
    public function test_stock_and_balance_failures_do_not_create_orders(): void {
        [, $product,$variant] = $this->fixture();
        $user = User::factory()->create(['balance'=>10]);
        $body = ['variant_id'=>$variant->id,'quantity'=>1,'recipient'=>'Player','request_id'=>(string)Str::uuid(),'accept_terms'=>1];
        $this->actingAs($user)->post(route('orders.store',$product),$body)->assertSessionHasErrors('balance');
        $body['quantity'] = 4;
        $this->post(route('orders.store',$product),$body)->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('service_orders',0);
        $this->assertSame(3,$variant->fresh()->stock);
    }
    public function test_admin_creates_variants_and_cannot_make_category_cycle(): void {
        [$category] = $this->fixture();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->post(route('admin.products.store'),[
            'name'=>'New service','category_id'=>$category->id,'recipient_label'=>'UID','is_active'=>1,
            'variants'=>[['name'=>'Small','price'=>12.5,'stock'=>5,'is_active'=>1],['name'=>'Large','price'=>25,'stock'=>null,'is_active'=>1]],
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products',['name'=>'New service']);
        $this->assertDatabaseHas('product_variants',['name'=>'Small','price'=>12.5]);
        $child = Category::create(['name'=>'Child','slug'=>'child','parent_id'=>$category->id]);
        $this->put(route('admin.categories.update',$category),['name'=>'Game','slug'=>'game','parent_id'=>$child->id])->assertSessionHasErrors('parent_id');
    }
}
