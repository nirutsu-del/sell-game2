<?php

namespace Tests\Feature;

use App\Models\{ContactMessage, ContactAttachment, User, Category, Product, ServiceOrder, GameAccount, PurchaseHistory, GachaBox, GachaItem, GachaSpin};
use App\Services\ContactOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\{Storage, URL};
use Illuminate\Support\Str;
use Tests\TestCase;

class ContactSupportTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        return ['name'=>'Customer','email'=>'customer@example.com','subject'=>'Support request','message'=>'Please help','category'=>'account'];
    }

    public function test_waiting_side_changes_on_reply_but_not_on_read_and_filters_match(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($user)->post(route('contact.store'),$this->data())->assertSessionHasNoErrors();
        $message = ContactMessage::firstOrFail();
        $this->assertSame('รอร้านตอบ',$message->statusLabel());
        $this->actingAs($admin)->patch(route('admin.contacts.update',$message),['action'=>'read']);
        $this->assertSame('staff',$message->fresh()->waiting_on);
        $this->post(route('admin.contacts.reply',$message),['body'=>'Need details']);
        $this->assertSame('รอลูกค้าตอบ',$message->fresh()->statusLabel());
        $this->get(route('admin.contacts.index',['status'=>'customer']))->assertViewHas('messages',fn($rows)=>$rows->total()===1);
        $this->get(route('admin.contacts.index',['status'=>'staff']))->assertViewHas('messages',fn($rows)=>$rows->total()===0);
        $this->patch(route('admin.contacts.update',$message),['action'=>'resolve']);
        $this->assertSame('จัดการแล้ว',$message->fresh()->statusLabel());
        $this->actingAs($user)->post(route('contact.reply',$message),['body'=>'More details']);
        $this->assertSame('รอร้านตอบ',$message->fresh()->statusLabel());
    }

    public function test_order_selection_accepts_only_owned_references_and_valid_categories(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        $product = Product::create(['category_id'=>$category->id,'name'=>'Service']);
        $variant = $product->variants()->create(['name'=>'Small','price'=>10]);
        $service = ServiceOrder::create(['user_id'=>$user->id,'product_variant_id'=>$variant->id,'request_id'=>(string)Str::uuid(),'product_name'=>'Service','variant_name'=>'Small','quantity'=>1,'total'=>10,'recipient'=>'PRIVATE RECIPIENT']);
        $account = GameAccount::create(['category_id'=>$category->id,'title'=>'Account','price'=>10,'credentials_data'=>['password'=>'PRIVATE PASSWORD']]);
        $purchase = PurchaseHistory::create(['user_id'=>$user->id,'game_account_id'=>$account->id,'price_paid'=>10,'account_data_delivered'=>'PRIVATE DELIVERY','source'=>'shop']);
        $box = GachaBox::create(['name'=>'Box','price_per_spin'=>10]);
        $item = GachaItem::create(['gacha_box_id'=>$box->id,'reward_type'=>'credit','credit_amount'=>1,'drop_rate'=>1]);
        $spin = GachaSpin::create(['user_id'=>$user->id,'gacha_box_id'=>$box->id,'gacha_item_id'=>$item->id,'price_paid'=>10]);
        $refs = ['S-'.$service->id,'A-'.$purchase->id,'G-'.$spin->id];
        $this->actingAs($user)->get(route('contact'))->assertOk()->assertSee($refs)->assertDontSee('PRIVATE PASSWORD')->assertDontSee('PRIVATE RECIPIENT');
        foreach ($refs as $ref) {
            $this->post(route('contact.store'),$this->data()+['order_reference'=>strtolower($ref)])->assertSessionHasNoErrors();
            $this->assertSame($ref,ContactMessage::latest('id')->first()->order_reference);
        }
        $this->actingAs($other)->get(route('contact'))->assertViewHas('orderOptions',[]);
        foreach ($refs as $ref) $this->post(route('contact.store'),$this->data()+['order_reference'=>$ref])->assertSessionHasErrors('order_reference');
        $this->post(route('contact.store'),array_replace($this->data(),['category'=>'invalid']))->assertSessionHasErrors('category');
        $this->assertDatabaseCount('contact_messages',3);
        $this->assertSame([],ContactOrders::options(null));
    }

    public function test_images_are_private_scoped_and_work_for_initial_and_both_reply_types(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['role'=>'admin']);
        $this->actingAs($user)->post(route('contact.store'),$this->data()+['attachments'=>[$this->paymentSlip()]])->assertSessionHasNoErrors();
        $message = ContactMessage::firstOrFail();
        $attachment = ContactAttachment::firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        Storage::disk('public')->assertMissing($attachment->path);
        $params = ['message'=>$message->id,'attachment'=>$attachment->id];
        $url = route('contact.attachment',$params);
        $this->get($url)->assertOk()->assertHeader('Content-Type','image/png')->assertHeader('Cache-Control','no-store, private');
        $this->get(route('contact.show',$message))->assertSee($url);
        $this->post(route('contact.reply',$message),['body'=>'My screenshot','attachments'=>[$this->paymentSlip()]])->assertSessionHasNoErrors();
        $this->actingAs($other)->get($url)->assertNotFound();
        $otherMessage = ContactMessage::create($this->data()+['user_id'=>$other->id]);
        $this->get(route('contact.attachment',['message'=>$otherMessage->id,'attachment'=>$attachment->id]))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.contacts.attachment',$params))->assertOk();
        $this->post(route('admin.contacts.reply',$message),['body'=>'Instructions','attachments'=>[$this->paymentSlip()]])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_attachments',3);
        $this->assertSame(2,$message->attachments()->whereNotNull('contact_reply_id')->count());
    }

    public function test_guest_attachment_requires_signed_link_for_the_same_thread(): void
    {
        Storage::fake('local');
        $this->post(route('contact.store'),$this->data()+['attachments'=>[$this->paymentSlip()]])->assertSessionHasNoErrors();
        $attachment = ContactAttachment::firstOrFail();
        $params = ['message'=>$attachment->contact_message_id,'attachment'=>$attachment->id];
        $url = URL::signedRoute('contact.guest.attachment',$params);
        $this->get($url)->assertOk();
        $this->get(route('contact.guest.attachment',$params))->assertForbidden();
        $this->get(route('contact.attachment',$params))->assertRedirect(route('login'));
        $other = ContactMessage::create($this->data());
        $this->get(URL::signedRoute('contact.guest.attachment',['message'=>$other->id,'attachment'=>$attachment->id]))->assertNotFound();
        $this->post(route('contact.store'),$this->data()+['order_reference'=>'A-1'])->assertSessionHasErrors('order_reference');
    }

    public function test_invalid_uploads_are_rejected_before_any_files_or_messages_are_saved(): void
    {
        Storage::fake('local');
        foreach ([
            [UploadedFile::fake()->createWithContent('fake.png','<?php echo 1; ?>')],
            [UploadedFile::fake()->createWithContent('bad.svg','<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>')],
            [$this->paymentSlip()->size(2049)],
            [$this->paymentSlip(),$this->paymentSlip(),$this->paymentSlip(),$this->paymentSlip()],
        ] as $files) {
            $this->post(route('contact.store'),$this->data()+['attachments'=>$files])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('contact_messages',0);
        $this->assertSame([],Storage::disk('local')->allFiles());
    }

    public function test_failed_notification_removes_uploaded_files_and_rolls_back_reply_state(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        User::factory()->create(['role'=>'admin']);
        $message = ContactMessage::create($this->data()+['user_id'=>$user->id]);
        $message->waiting_on = 'customer';
        $message->save();
        DatabaseNotification::creating(fn()=>throw new \RuntimeException('Notification failed'));
        $this->actingAs($user)->withoutExceptionHandling();
        try {
            $this->post(route('contact.reply',$message),['body'=>'Screenshot','attachments'=>[$this->paymentSlip()]]);
            $this->fail('Expected notification failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Notification failed',$e->getMessage());
        } finally { DatabaseNotification::flushEventListeners(); }
        $this->assertDatabaseCount('contact_replies',0);
        $this->assertDatabaseCount('contact_attachments',0);
        $this->assertSame('customer',$message->fresh()->waiting_on);
        $this->assertSame([],Storage::disk('local')->allFiles());
    }

    public function test_migration_preserves_closed_threads_and_backfills_from_latest_reply(): void
    {
        $staffLast = ContactMessage::create($this->data());
        $staffLast->replies()->create(['body'=>'Store answer','from_staff'=>true]);
        $customerLast = ContactMessage::create($this->data());
        $customerLast->replies()->create(['body'=>'Store answer','from_staff'=>true]);
        $customerLast->replies()->create(['body'=>'Customer answer','from_staff'=>false]);
        $closed = ContactMessage::create($this->data());
        $closed->resolved_at = now();
        $closed->resolution_note = 'Keep private note';
        $closed->save();
        $migration = require database_path('migrations/2026_09_19_000002_extend_contact_support.php');
        $migration->down();
        $migration->up();
        $this->assertSame('customer',$staffLast->fresh()->waiting_on);
        $this->assertSame('staff',$customerLast->fresh()->waiting_on);
        $this->assertSame('จัดการแล้ว',$closed->fresh()->statusLabel());
        $this->assertSame('Keep private note',$closed->fresh()->resolution_note);
        $this->assertSame('general',$closed->fresh()->category);
    }
}
