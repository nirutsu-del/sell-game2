<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\GameAccount;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
class DemoCatalogTest extends TestCase {
    use RefreshDatabase;
    public function test_demo_seed_is_repeatable_and_shows_disclosed_product_data(): void {
        Storage::fake('public');
        foreach(config('demo_catalog') as $slug=>$item) {
            Category::firstOrCreate(['name'=>$item['game']],['slug'=>Str::slug($item['game'])]);
            Storage::disk('public')->put('demo-accounts/'.$slug.'.webp','demo');
        }
        $this->seed(DemoCatalogSeeder::class);
        $account=GameAccount::first();
        $account->update(['status'=>'sold']);
        $this->seed(DemoCatalogSeeder::class);
        $this->assertDatabaseCount('game_accounts',8);
        $this->assertSame('sold',$account->fresh()->status);
        $this->get('/')->assertOk()->assertDontSee('demo-site-notice')->assertSee('Galaxy Voyager')->assertSee('32 ไอเทม');
        $this->get(route('accounts.show',GameAccount::where('status','available')->first()))->assertOk()->assertSee('ไม่มีบัญชีเกมจริง');
    }
}
