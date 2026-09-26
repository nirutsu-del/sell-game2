<?php

namespace Tests\Feature;

use App\Models\{StorePolicy, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_malformed_content_returns_to_a_working_form_and_public_page_has_no_account_menu(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->from(route('notifications.index'))->put(route('admin.policies.update', 'terms'), [
            '_policy' => 'terms', 'content' => ['bad'], 'is_published' => ['bad'],
        ])->assertRedirect(route('admin.policies.edit'))->assertSessionHasErrors(['content', 'is_published']);
        $this->get(route('admin.policies.edit'))->assertOk();
        $this->assertDatabaseCount('store_policies', 0);
        foreach (array_keys(StorePolicy::PAGES) as $slug) {
            $this->get(route('policies.show', $slug))->assertOk()->assertDontSee('aria-label="เมนูบัญชี"', false);
        }
    }

    public function test_policy_pages_and_footer_are_available_without_login(): void
    {
        foreach (StorePolicy::PAGES as $slug => $title) {
            $this->get(route('policies.show', $slug))->assertOk()->assertSee($title)->assertSee('ร้านยังไม่ได้เผยแพร่ข้อมูลหน้านี้');
            $this->get(route('login'))->assertSee(route('policies.show', $slug));
        }
        $this->get('/policies/unknown')->assertNotFound();
    }

    public function test_admin_can_save_drafts_publish_and_hide_each_policy(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach (StorePolicy::PAGES as $slug => $title) {
            $url = route('admin.policies.update', $slug);
            $this->put($url, ['content' => 'Private policy draft', 'is_published' => 0])->assertRedirect(route('admin.policies.edit'));
            $this->get(route('policies.show', $slug))->assertDontSee('Private policy draft');
            $this->get(route('admin.policies.edit'))->assertOk()->assertSee('Private policy draft');
            $content = 'Policy <script>alert(1)</script>';
            $this->put($url, ['content' => $content, 'is_published' => 1])->assertSessionHasNoErrors();
            $this->get(route('policies.show', $slug))->assertSee(e($content), false)->assertDontSee($content, false)->assertSee('อัปเดตล่าสุด');
            $this->put($url, ['content' => $content, 'is_published' => 0])->assertSessionHasNoErrors();
            $this->get(route('policies.show', $slug))->assertDontSee(e($content), false);
        }
        $this->assertDatabaseCount('store_policies', 3);
    }

    public function test_only_admin_can_edit_and_blank_content_cannot_be_published(): void
    {
        $this->get(route('admin.policies.edit'))->assertRedirect(route('login'));
        $this->put(route('admin.policies.update', 'terms'), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'user']));
        $this->get(route('admin.policies.edit'))->assertForbidden();
        $this->put(route('admin.policies.update', 'terms'), [])->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $this->from(route('admin.policies.edit'))->put(route('admin.policies.update', 'terms'), ['_policy' => 'terms', 'content' => ' ', 'is_published' => 1])->assertSessionHasErrors('content');
        $this->get(route('admin.policies.edit'))->assertOk();
        $this->put(route('admin.policies.update', 'unknown'), [])->assertNotFound();
        $this->assertDatabaseCount('store_policies', 0);
    }
}
