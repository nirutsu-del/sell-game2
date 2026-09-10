<?php
namespace Tests\Feature;
use App\Models\{User, StoreSetting};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class StoreContactSettingsTest extends TestCase {
    use RefreshDatabase;
    private function data(): array {
        return ['facebook_url'=>'https://www.facebook.com/example','line_url'=>'https://line.me/example','discord_url'=>'https://discord.gg/example','opening_hours'=>'10:00–22:00','floating_contact_enabled'=>1,'announcement_enabled'=>1,'announcement'=>'Weekend promotion'];
    }
    public function test_admin_can_publish_contact_links_and_announcement_without_changing_qr(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        StoreSetting::create(['name'=>'My Store','promptpay_qr'=>'keep-qr.png']);
        $this->actingAs($admin)->put(route('admin.settings.contact.update'),$this->data())->assertSessionHasNoErrors();
        $this->assertSame('keep-qr.png',StoreSetting::current()->promptpay_qr);
        $this->get('/')->assertOk()->assertSee('Weekend promotion')->assertSee('floating-store-contact');
        $this->get(route('contact'))->assertOk()->assertSee('https://line.me/example')->assertSee('10:00–22:00');
        $this->get(route('admin.settings.edit'))->assertOk()->assertDontSee('id="floating-store-contact"',false);
    }
    public function test_disabled_settings_and_empty_channels_are_hidden(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        $data = $this->data();
        $data['announcement_enabled'] = 0;
        $data['floating_contact_enabled'] = 0;
        $this->actingAs($admin)->put(route('admin.settings.contact.update'),$data)->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Weekend promotion')->assertDontSee('floating-store-contact');
        $data['facebook_url'] = $data['line_url'] = $data['discord_url'] = '';
        $data['floating_contact_enabled'] = 1;
        $this->put(route('admin.settings.contact.update'),$data)->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('floating-store-contact');
        $this->assertSame([],StoreSetting::current()->contactLinks());
    }
    public function test_permissions_validation_and_html_escaping(): void {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('admin.settings.contact.update'),$this->data())->assertForbidden();
        $admin = User::factory()->create(['role'=>'admin']);
        foreach (['javascript:alert(1)','http://example.com','https://user:pass@example.com'] as $link) {
            $data = $this->data(); $data['facebook_url'] = $link;
            $this->actingAs($admin)->put(route('admin.settings.contact.update'),$data)->assertSessionHasErrors('facebook_url');
        }
        $data = $this->data(); $data['announcement'] = '';
        $this->put(route('admin.settings.contact.update'),$data)->assertSessionHasErrors('announcement');
        $data['announcement'] = '<script>alert(1)</script>';
        $this->put(route('admin.settings.contact.update'),$data)->assertSessionHasNoErrors();
        $this->get('/')->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
    }
}
