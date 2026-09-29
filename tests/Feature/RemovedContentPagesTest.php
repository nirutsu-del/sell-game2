<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemovedContentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_removed_pages_are_unavailable_to_guests_and_admins(): void
    {
        $paths = ['/news', '/news/example', '/policies/terms', '/policies/privacy', '/policies/refund', '/admin/news', '/admin/news/create', '/admin/news/1/edit', '/admin/policies'];
        foreach ($paths as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        foreach ($paths as $path) {
            $this->get($path)->assertNotFound();
        }
        $this->post('/admin/news', [])->assertNotFound();
        $this->put('/admin/news/1', [])->assertNotFound();
        $this->delete('/admin/news/1')->assertNotFound();
        $this->put('/admin/policies/terms', [])->assertNotFound();
    }

    public function test_store_and_admin_navigation_have_no_removed_links(): void
    {
        foreach (['shop.index', 'login', 'catalog.index'] as $route) {
            $this->get(route($route))->assertOk()
                ->assertDontSee(url('/news'), false)
                ->assertDontSee(url('/policies'), false);
        }
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee('ข่าวสาร')
            ->assertDontSee('นโยบายร้าน')
            ->assertSee(route('admin.settings.edit'), false);
    }
}