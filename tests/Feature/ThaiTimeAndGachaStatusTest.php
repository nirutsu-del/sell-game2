<?php
namespace Tests\Feature;
use App\Models\{Category, GameAccount, GachaBox, GachaItem, News, TopupTransaction, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThaiTimeAndGachaStatusTest extends TestCase {
    use RefreshDatabase;
    public function test_thai_display_rolls_into_next_day_without_changing_stored_time(): void {
        $user = User::factory()->create();
        $topup = TopupTransaction::create(['user_id'=>$user->id,'amount'=>100,'payment_method'=>'promptpay_slip','reference_no'=>'TIME-TEST']);
        $topup->forceFill(['created_at'=>'2026-09-07 18:30:00'])->save();
        $this->actingAs($user)->get(route('user.dashboard'))->assertOk()->assertSee('08/09/2026 01:30')->assertDontSee('07/09/2026 18:30');
        $this->assertSame('2026-09-07 18:30:00',$topup->fresh()->getRawOriginal('created_at'));
        $user->notify(new \App\Notifications\StoreNotification('Time test','Message','topup',$topup->id));
        $user->notifications()->update(['created_at'=>'2026-09-07 18:30:00']);
        $this->get(route('notifications.index'))->assertOk()->assertSee('08/09/2026 01:30');
        $news = News::create(['title'=>'Time news','slug'=>'time-news','content'=>'Content','is_published'=>true,'published_at'=>'2026-09-07 18:30:00']);
        $this->get(route('news.show',$news))->assertOk()->assertSee('08/09/2026');
        $news->update(['published_at'=>null]);
        $this->get(route('news.show',$news))->assertOk();
    }
    public function test_credit_only_box_is_ready_and_zero_weight_is_empty(): void {
        $box = GachaBox::create(['name'=>'Credit box','price_per_spin'=>30,'is_active'=>true]);
        $item = GachaItem::create(['gacha_box_id'=>$box->id,'reward_type'=>'credit','credit_amount'=>5,'drop_rate'=>100]);
        $this->get(route('gacha.index'))->assertOk()->assertSee('พร้อมสุ่มรางวัลเครดิต')->assertDontSee('ไอดีหมดชั่วคราว');
        $this->get(route('gacha.show',$box))->assertOk()->assertSee('100.0000%');
        $item->update(['drop_rate'=>0]);
        $this->get(route('gacha.index'))->assertOk()->assertSee('รางวัลหมดชั่วคราว')->assertDontSee('พร้อมสุ่มรางวัลเครดิต');
    }
    public function test_stock_counts_only_available_positive_weight_accounts_and_labels_mixed_rewards(): void {
        $box = GachaBox::create(['name'=>'Mixed','price_per_spin'=>30,'is_active'=>true]);
        $category = Category::create(['name'=>'Game','slug'=>'game']);
        foreach ([['available',1],['sold',1],['available',0]] as [$status,$weight]) {
            $account = GameAccount::create(['category_id'=>$category->id,'title'=>'Account','price'=>100,'status'=>$status,'credentials_data'=>['username'=>'test']]);
            GachaItem::create(['gacha_box_id'=>$box->id,'game_account_id'=>$account->id,'reward_type'=>'game_account','drop_rate'=>$weight]);
        }
        $this->get(route('gacha.index'))->assertOk()->assertSee('เหลือ 1 ไอดี');
        $this->assertSame(1,$box->availableAccountsCount());
        GachaItem::create(['gacha_box_id'=>$box->id,'reward_type'=>'credit','credit_amount'=>5,'drop_rate'=>1]);
        $this->get(route('gacha.index'))->assertOk()->assertSee('เหลือ 1 ไอดี + รางวัลเครดิต');
        $box->update(['is_active'=>false]);
        $this->get(route('gacha.index'))->assertOk()->assertDontSee('เหลือ 1 ไอดี');
    }
}
