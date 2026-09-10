<?php
namespace Tests\Feature;
use App\Models\{Category, Product, GameAccount, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ServiceCatalogDisabledTest extends TestCase {
    use RefreshDatabase;
    public function test_service_catalog_is_hidden_and_direct_access_is_blocked_by_default(): void {
        $this->assertFalse(config('store.service_catalog_enabled'));
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Hidden legacy service','is_active'=>true]);
        $account = GameAccount::create(['category_id'=>$category->id,'title'=>'Available account','price'=>100,'status'=>'available','credentials_data'=>['username'=>'test']]);
        $this->get('/')->assertOk()->assertDontSee('Hidden legacy service')->assertSee('Available account');
        $this->get(route('catalog.index'))->assertOk()->assertDontSee('สินค้าและบริการ')->assertSee('Available account');
        $this->get(route('products.show',$product))->assertNotFound();
        $buyer = User::factory()->create(['balance'=>100]);
        $this->actingAs($buyer)->post(route('orders.store',$product),[])->assertNotFound();
        $this->get(route('orders.index'))->assertOk()->assertDontSee('งานบริการ');
        $this->assertSame('100.00',$buyer->fresh()->balance);
        $this->assertDatabaseCount('service_orders',0);
        $this->assertDatabaseHas('products',['id'=>$product->id]);
        $this->get(route('accounts.show',$account))->assertOk();
        $this->actingAs(User::factory()->create(['role'=>'admin']))->get(route('admin.dashboard'))->assertOk()->assertDontSee('คิวงานบริการ')->assertDontSee('สินค้าและแพ็กเกจ');
        $this->get(route('admin.products.index'))->assertNotFound();
        $this->get(route('admin.service-orders.index'))->assertNotFound();
        $this->post(route('admin.products.store'),[])->assertNotFound();
    }
}
