<?php
namespace Tests\Feature;
use App\Models\{User, WalletTransaction, PurchaseHistory, ServiceOrder, GachaBox, TopupTransaction, Category, Product};
use App\Services\SalesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;
class SalesReportTest extends TestCase {
    use RefreshDatabase;
    private function entry(User $user, string $ref, string $type, string $amount, string $date, string $description = 'Entry'): WalletTransaction {
        $entry = WalletTransaction::create(['user_id'=>$user->id,'reference_type'=>$ref,'reference_id'=>1,'type'=>$type,'amount'=>$amount,'description'=>$description]);
        $entry->created_at = $date; $entry->save();
        return $entry;
    }
    public function test_summary_separates_topups_refunds_rewards_and_thai_day_boundaries(): void {
        $user = User::factory()->create();
        $this->entry($user,PurchaseHistory::class,'debit','100.25','2026-09-06 17:00:00');
        $this->entry($user,ServiceOrder::class,'debit','200.50','2026-09-07 10:00:00');
        $this->entry($user,GachaBox::class,'debit','30.00','2026-09-07 16:59:59');
        $this->entry($user,ServiceOrder::class,'credit','50.00','2026-09-07 12:00:00');
        $this->entry($user,GachaBox::class,'credit','5.25','2026-09-07 12:00:00');
        $this->entry($user,TopupTransaction::class,'credit','1000.00','2026-09-07 12:00:00');
        $this->entry($user,PurchaseHistory::class,'debit','999.00','2026-09-07 17:00:00');
        $service = app(SalesReportService::class);
        $period = $service->period(new Request(['period'=>'custom','from'=>'2026-09-07','to'=>'2026-09-07']));
        $result = $service->summary($period);
        $this->assertSame(33075,$result['gross']);
        $this->assertSame(27550,$result['net']);
        $this->assertSame(3,$result['paidCount']);
        $this->assertSame(100000,$result['totals']['topups']['cents']);
    }
    public function test_later_refund_is_reported_on_refund_date_and_can_be_negative(): void {
        $user = User::factory()->create();
        $this->entry($user,ServiceOrder::class,'debit','100.00','2026-09-01 10:00:00');
        $this->entry($user,ServiceOrder::class,'credit','100.00','2026-09-07 10:00:00');
        $service = app(SalesReportService::class);
        $report = $service->summary($service->period(new Request(['period'=>'custom','from'=>'2026-09-07','to'=>'2026-09-07'])));
        $this->assertSame(-10000,$report['net']);
        $this->assertSame(0,$report['gross']);
    }
    public function test_report_permissions_validation_and_csv_safety(): void {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.reports.sales'))->assertForbidden();
        $this->get(route('admin.reports.sales.export'))->assertForbidden();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->entry($user,GachaBox::class,'debit','10.50','2026-09-07 10:00:00','=HYPERLINK("bad")');
        $this->actingAs($admin)->get(route('admin.reports.sales',['period'=>'all']))->assertOk();
        $this->get(route('admin.reports.sales',['period'=>'custom','from'=>'2026-09-09','to'=>'2026-09-01']))->assertSessionHasErrors('to');
        $response = $this->get(route('admin.reports.sales.export',['period'=>'all']))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringStartsWith(hex2bin('efbbbf'),$csv);
        $this->assertStringContainsString("'=HYPERLINK",$csv);
        $this->assertStringContainsString('2026-09-07 17:00:00',$csv);
        $this->assertStringContainsString('10.50',$csv);
        $this->assertStringNotContainsString($user->email,$csv);
    }
    public function test_service_ranking_counts_quantity_and_dashboard_includes_services(): void {
        $user = User::factory()->create();
        $category = Category::create(['name'=>'Test','slug'=>'test']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Gift']);
        $variant = $product->variants()->create(['name'=>'Pack','price'=>20]);
        $order = ServiceOrder::create(['user_id'=>$user->id,'product_variant_id'=>$variant->id,'product_name'=>'Gift','variant_name'=>'Pack','quantity'=>3,'total'=>60,'recipient'=>'User','request_id'=>(string)Str::uuid()]);
        $entry = $this->entry($user,ServiceOrder::class,'debit','60.00','2026-09-07 10:00:00');
        $entry->update(['reference_id'=>$order->id]);
        $rank = app(SalesReportService::class)->bestSellers(['start'=>null,'end'=>null]);
        $this->assertSame(3,$rank->first()['units']);
        $this->assertSame(6000,$rank->first()['cents']);
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertViewHas('stats',fn($s)=>$s['sales']==60);
    }
}
