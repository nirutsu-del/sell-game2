<?php
namespace Tests\Feature;
use App\Models\{StoreSetting, StoreBanner, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
class StoreSettingsTest extends TestCase {
    use RefreshDatabase;
    private function png(string $name): UploadedFile {
        return UploadedFile::fake()->createWithContent($name,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII='));
    }
    public function test_only_admin_can_change_settings_and_default_qr_is_preserved(): void {
        config(['services.promptpay.qr_image'=>'/storage/original.png']);
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.settings.edit'))->assertForbidden();
        $this->put(route('admin.settings.update'),['name'=>'Unauthorized'])->assertForbidden();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->assertSee('/storage/original.png');
        $this->put(route('admin.settings.update'),['name'=>'My Store','description'=>'Welcome'])->assertSessionHasNoErrors();
        $this->get('/')->assertOk()->assertSee('My Store')->assertSee('Welcome');
        $this->assertSame('/storage/original.png',StoreSetting::current()->qrUrl('promptpay'));
    }
    public function test_uploads_are_served_and_unselected_images_are_not_cleared(): void {
        Storage::fake('public');
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($admin)->put(route('admin.settings.update'),[
            'name'=>'My Store','logo'=>$this->png('logo.png'),
            'promptpay_qr'=>$this->png('qr.png'),'truemoney_qr'=>$this->png('true.png'),
            'promptpay_instructions'=>'Check recipient first',
        ])->assertSessionHasNoErrors();
        $settings = StoreSetting::current();
        Storage::disk('public')->assertExists([$settings->logo,$settings->promptpay_qr,$settings->truemoney_qr]);
        $this->put(route('admin.settings.update'),['name'=>'Renamed'])->assertSessionHasNoErrors();
        $this->assertSame($settings->promptpay_qr,StoreSetting::current()->promptpay_qr);
        $this->assertDatabaseCount('store_settings',1);
        $this->get(route('wallet.index'))->assertOk()->assertSee(asset('storage/'.$settings->promptpay_qr))->assertSee('Check recipient first');
    }
    public function test_banners_can_be_ordered_hidden_and_keep_image_on_edit(): void {
        Storage::fake('public');
        $admin = User::factory()->create(['role'=>'admin']);
        foreach (['Later'=>20,'First'=>1] as $title=>$sort) {
            $this->actingAs($admin)->post(route('admin.settings.banners.store'),[
                'title'=>$title,'sort_order'=>$sort,'is_active'=>1,'link'=>'/categories','image'=>$this->png($title.'.png'),
            ])->assertSessionHasNoErrors();
        }
        $this->get('/')->assertOk()->assertSeeInOrder(['alt="First"','alt="Later"'],false);
        $banner = StoreBanner::where('title','First')->firstOrFail();
        $image = $banner->image;
        $this->put(route('admin.settings.banners.update',$banner),['title'=>'Hidden banner','sort_order'=>0,'link'=>'https://example.com'])->assertSessionHasNoErrors();
        $this->assertSame($image,$banner->fresh()->image);
        $this->get('/')->assertDontSee('Hidden banner');
        $this->get(route('admin.settings.edit'))->assertSee('Hidden banner');
    }
    public function test_unsafe_links_and_non_images_are_rejected(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        $banner = StoreBanner::create(['title'=>'Banner','image'=>'banner.png']);
        foreach (['javascript:alert(1)','//example.com','/\\example.com','data:text/html,test'] as $link) {
            $this->actingAs($admin)->put(route('admin.settings.banners.update',$banner),['title'=>'Banner','sort_order'=>0,'link'=>$link])->assertSessionHasErrors('link');
        }
        $this->put(route('admin.settings.update'),['name'=>'Store','logo'=>UploadedFile::fake()->create('payload.svg',10,'image/svg+xml')])->assertSessionHasErrors('logo');
    }
}
