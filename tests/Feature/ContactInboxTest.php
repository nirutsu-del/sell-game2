<?php
namespace Tests\Feature;
use App\Models\{User, ContactMessage};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;
class ContactInboxTest extends TestCase {
    use RefreshDatabase;
    private function data(): array {
        return ['name'=>'Customer','email'=>'customer@example.com','subject'=>'Order S-12','message'=>'Private message content'];
    }
    public function test_guest_submission_notifies_admin_and_links_to_inbox(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        $user = User::factory()->create();
        $this->post(route('contact.store'),$this->data())->assertSessionHas('success');
        $message = ContactMessage::firstOrFail();
        $this->assertNull($message->user_id);
        $this->assertSame(1,$admin->notifications()->count());
        $this->assertSame(0,$user->notifications()->count());
        $notice = $admin->notifications()->first();
        $this->assertStringNotContainsString('Private message',$notice->data['message']);
        $this->actingAs($admin)->post(route('notifications.open',$notice->id))->assertRedirect(route('admin.contacts.show',$message));
        $this->get(route('admin.contacts.show',$message))->assertOk()->assertSee('Private message content');
        $this->assertNull($message->fresh()->read_at);
    }
    public function test_non_admin_cannot_read_or_change_messages(): void {
        $message = ContactMessage::create($this->data());
        $user = User::factory()->create();
        $this->get(route('admin.contacts.index'))->assertRedirect(route('login'));
        $this->actingAs($user)->get(route('admin.contacts.index'))->assertForbidden();
        $this->get(route('admin.contacts.show',$message))->assertForbidden();
        $this->patch(route('admin.contacts.update',$message),['action'=>'resolve'])->assertForbidden();
        $this->assertNull($message->fresh()->resolved_at);
    }
    public function test_read_resolve_filters_and_repeated_resolution_preserve_original_actor(): void {
        $message = ContactMessage::create($this->data());
        $admin = User::factory()->create(['role'=>'admin','name'=>'First admin']);
        $url = route('admin.contacts.update',$message);
        $this->actingAs($admin)->get(route('admin.contacts.index',['status'=>'new']))->assertViewHas('messages',fn($p)=>$p->total()===1);
        $this->patch($url,['action'=>'read'])->assertSessionHasNoErrors();
        $this->assertNotNull($message->fresh()->read_at);
        $this->get(route('admin.contacts.index',['status'=>'new']))->assertViewHas('messages',fn($p)=>$p->total()===0);
        $this->get(route('admin.contacts.index',['status'=>'read','q'=>'C-'.$message->id]))->assertViewHas('messages',fn($p)=>$p->total()===1);
        $this->patch($url,['action'=>'resolve','resolution_note'=>'Handled offline'])->assertSessionHasNoErrors();
        $this->assertSame('First admin',$message->fresh()->resolved_by_name);
        $second = User::factory()->create(['role'=>'admin']);
        $this->actingAs($second)->patch($url,['action'=>'resolve','resolution_note'=>'Overwrite'])->assertSessionHasNoErrors();
        $this->assertSame('Handled offline',$message->fresh()->resolution_note);
        $this->get(route('admin.contacts.index',['status'=>'resolved','q'=>'customer@example.com']))->assertViewHas('messages',fn($p)=>$p->total()===1);
    }
    public function test_message_is_escaped_and_failed_notification_rolls_back_submission(): void {
        $admin = User::factory()->create(['role'=>'admin']);
        $data = $this->data(); $data['message'] = '<script>alert(1)</script>';
        $message = ContactMessage::create($data);
        $this->actingAs($admin)->get(route('admin.contacts.show',$message))->assertSee('&lt;script&gt;',false)->assertDontSee('<script>alert(1)</script>',false);
        DatabaseNotification::creating(fn()=>throw new \RuntimeException('Notification failed'));
        $this->withoutExceptionHandling();
        try {
            $this->post(route('contact.store'),$this->data());
            $this->fail('Expected failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Notification failed',$e->getMessage());
        } finally { DatabaseNotification::flushEventListeners(); }
        $this->assertDatabaseCount('contact_messages',1);
    }
}
