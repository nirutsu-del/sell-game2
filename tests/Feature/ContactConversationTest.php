<?php

namespace Tests\Feature;

use App\Models\{ContactMessage, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ContactConversationTest extends TestCase
{
    use RefreshDatabase;

    private function message(?User $user = null): ContactMessage
    {
        return ContactMessage::create(['user_id'=>$user?->id,'name'=>'Customer','email'=>'customer@example.com','subject'=>'Help with order','message'=>'Initial message']);
    }

    public function test_members_can_only_read_and_reply_to_their_own_threads(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $message = $this->message($owner);
        $this->actingAs($other)->get(route('contact.show',$message))->assertNotFound();
        $this->post(route('contact.reply',$message),['body'=>'Intrusion'])->assertNotFound();
        $this->get(route('contact.index'))->assertDontSee('Help with order');
        $this->actingAs($owner)->get(route('contact.index'))->assertSee('Help with order');
        $this->get(route('contact.show',$message))->assertOk()->assertHeader('Cache-Control','no-store, private');
        $this->post(route('contact.reply',$message),['body'=>'More details'])->assertRedirect(route('contact.show',$message));
        $this->assertDatabaseCount('contact_replies',1);
        $this->assertFalse($message->replies()->first()->from_staff);
        $this->get(URL::signedRoute('contact.guest.show',$message))->assertNotFound();
    }

    public function test_guest_tracking_requires_valid_signature_and_does_not_claim_by_email(): void
    {
        $this->post(route('contact.store'),['name'=>'Guest','email'=>'same@example.com','subject'=>'Guest request','message'=>'Private guest message'])->assertSessionHas('contact_tracking_url');
        $message = ContactMessage::firstOrFail();
        $url = session('contact_tracking_url');
        $this->get($url)->assertOk()->assertSee('Private guest message');
        $this->get(route('contact.guest.show',$message))->assertForbidden();
        $this->post(route('contact.guest.reply',$message),['body'=>'Invalid'])->assertForbidden();
        $this->post(URL::signedRoute('contact.guest.reply',$message),['body'=>'Guest follow-up','from_staff'=>true])->assertRedirect($url);
        $this->assertFalse($message->replies()->first()->from_staff);
        $user = User::factory()->create(['email'=>'same@example.com']);
        $this->actingAs($user)->get(route('contact.show',$message))->assertNotFound();
        $this->get(route('contact.index'))->assertDontSee('Guest request');
    }

    public function test_staff_reply_close_and_customer_reopen_notify_and_keep_internal_notes_private(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role'=>'admin']);
        $message = $this->message($owner);
        $this->actingAs($owner)->post(route('admin.contacts.reply',$message),['body'=>'Fake staff'])->assertForbidden();
        $this->actingAs($admin)->post(route('admin.contacts.reply',$message),['body'=>'<script>alert(1)</script>'])->assertSessionHasNoErrors();
        $this->assertTrue($message->replies()->first()->from_staff);
        $this->patch(route('admin.contacts.update',$message),['action'=>'resolve','resolution_note'=>'SECRET STAFF NOTE'])->assertSessionHasNoErrors();
        $this->assertSame(2,$owner->notifications()->count());
        $notice = $owner->notifications()->first();
        $this->actingAs($owner)->post(route('notifications.open',$notice->id))->assertRedirect(route('contact.show',$message));
        $this->get(route('contact.show',$message))->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertDontSee('SECRET STAFF NOTE');
        $this->post(route('contact.reply',$message),['body'=>'Still need help'])->assertSessionHasNoErrors();
        $this->assertNull($message->fresh()->resolved_at);
        $this->assertNull($message->fresh()->read_at);
        $this->assertSame(1,$admin->notifications()->count());
        $this->actingAs($admin)->get(route('admin.contacts.index',['status'=>'new']))->assertSee('Help with order');
        $this->get(route('admin.contacts.show',$message))->assertSee('Still need help');
    }

    public function test_reply_validation_and_notification_failure_leave_thread_unchanged(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create(['role'=>'admin']);
        $message = $this->message($owner);
        $message->resolved_at = now();
        $message->save();
        $this->actingAs($owner)->post(route('contact.reply',$message),['body'=>'   '])->assertSessionHasErrors('body');
        $this->post(route('contact.reply',$message),['body'=>str_repeat('x',3001)])->assertSessionHasErrors('body');
        DatabaseNotification::creating(fn()=>throw new \RuntimeException('Notification failed'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('contact.reply',$message),['body'=>'Should roll back']);
            $this->fail('Expected notification failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Notification failed',$e->getMessage());
        } finally {
            DatabaseNotification::flushEventListeners();
        }
        $this->assertDatabaseCount('contact_replies',0);
        $this->assertNotNull($message->fresh()->resolved_at);
    }

    public function test_guest_pagination_links_are_signed_and_tampering_fails(): void
    {
        $message = $this->message();
        for ($i=1; $i<=31; $i++) $message->replies()->create(['body'=>'Reply '.$i,'from_staff'=>true]);
        $page2 = URL::signedRoute('contact.guest.show',['message'=>$message->id,'page'=>2]);
        $this->get(URL::signedRoute('contact.guest.show',$message))->assertSee($page2);
        $this->get($page2)->assertOk()->assertSee('Reply 31');
        $this->get(str_replace('page=2','page=3',$page2))->assertForbidden();
    }
}
