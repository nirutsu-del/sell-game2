<?php

namespace Tests\Feature;

use App\Models\{News, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_malformed_input_returns_to_a_working_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $news = News::create(['title' => 'Original', 'slug' => 'original', 'content' => 'Original content']);
        foreach ([['post', route('admin.news.store'), route('admin.news.create')], ['put', route('admin.news.update', $news), route('admin.news.edit', $news)]] as [$method, $url, $form]) {
            $this->from(route('notifications.index'))->{$method}($url, ['title' => ['bad'], 'content' => ['bad'], 'is_published' => ['bad']])
                ->assertRedirect($form)->assertSessionHasErrors(['title', 'content', 'is_published']);
            $this->get($form)->assertOk();
        }
        $this->assertDatabaseCount('news', 1);
        $this->assertSame('Original', $news->fresh()->title);
    }

    public function test_admin_can_create_publish_edit_unpublish_and_delete_news(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.news.create'))->assertOk();
        $this->post(route('admin.news.store'), ['title' => 'ข่าวทดสอบ', 'content' => 'รายละเอียดข่าว', 'is_published' => 0])->assertRedirect(route('admin.news.index'));
        $news = News::firstOrFail();
        $slug = $news->slug;
        $this->assertNull($news->published_at);
        $this->get(route('admin.news.edit', $news))->assertOk()->assertSee('รายละเอียดข่าว');
        $this->get(route('admin.news.index'))->assertOk()->assertSee('ฉบับร่าง');
        $this->get(route('news.show', $news))->assertNotFound();
        $this->get(route('news.index'))->assertDontSee('ข่าวทดสอบ')->assertSee('ยังไม่มีข่าวสารในขณะนี้');
        $data = ['title' => 'ข่าวเผยแพร่', 'content' => '<script>alert(1)</script>', 'is_published' => 1];
        $this->put(route('admin.news.update', $news), $data)->assertRedirect(route('admin.news.index'));
        $news->refresh();
        $publishedAt = $news->published_at->toDateTimeString();
        $this->assertSame($slug, $news->slug);
        $this->get(route('news.index'))->assertSee('ข่าวเผยแพร่');
        $this->get(route('news.show', $news))->assertOk()->assertSee(e($data['content']), false)->assertDontSee($data['content'], false);
        $this->travel(1)->days();
        $this->put(route('admin.news.update', $news), $data)->assertSessionHasNoErrors();
        $this->assertSame($publishedAt, $news->fresh()->published_at->toDateTimeString());
        $this->put(route('admin.news.update', $news), array_replace($data, ['is_published' => 0]))->assertSessionHasNoErrors();
        $this->get(route('news.show', $news))->assertNotFound();
        $this->delete(route('admin.news.destroy', $news))->assertRedirect(route('admin.news.index'));
        $this->assertDatabaseMissing('news', ['id' => $news->id]);
    }

    public function test_news_management_requires_admin_for_every_action(): void
    {
        $news = News::create(['title' => 'Private draft', 'slug' => 'draft', 'content' => 'Secret', 'is_published' => false]);
        $this->get(route('admin.news.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => 'user']));
        foreach (['index', 'create'] as $action) {
            $this->get(route('admin.news.'.$action))->assertForbidden();
        }
        $this->get(route('admin.news.edit', $news))->assertForbidden();
        $this->post(route('admin.news.store'), [])->assertForbidden();
        $this->put(route('admin.news.update', $news), [])->assertForbidden();
        $this->delete(route('admin.news.destroy', $news))->assertForbidden();
        $this->get(route('news.show', $news))->assertNotFound();
    }

    public function test_invalid_news_is_not_saved_and_public_news_is_readable_by_guests(): void
    {
        $news = News::create(['title' => 'Public news', 'slug' => 'public-news', 'content' => 'Read me', 'is_published' => true, 'published_at' => now()]);
        $this->get(route('news.show', $news))->assertOk()->assertSee('Read me');
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post(route('admin.news.store'), ['title' => '', 'content' => '', 'is_published' => 'invalid'])
            ->assertSessionHasErrors(['title', 'content', 'is_published']);
        $this->assertDatabaseCount('news', 1);
    }
}
