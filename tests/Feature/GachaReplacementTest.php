<?php
namespace Tests\Feature;
use App\Models\{Category,GameAccount,GachaBox,GachaItem,GachaSpin,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
class GachaReplacementTest extends TestCase {
    use RefreshDatabase;
    public function test_replacement_preserves_history_rate_and_retry_without_using_other_games(): void {
        $user=User::factory()->create(['balance'=>100]);
        $game=Category::create(['name'=>'Free Fire','slug'=>'ff']);
        $other=Category::create(['name'=>'Roblox','slug'=>'rb']);
        $make=fn($category,$title)=>GameAccount::create(['category_id'=>$category->id,'title'=>$title,'price'=>100,'status'=>'available','credentials_data'=>['username'=>$title]]);
        $first=$make($game,'First');$replacement=$make($game,'Replacement');$foreign=$make($other,'Other game');$assigned=$make($game,'Already assigned');
        $box=GachaBox::create(['name'=>'Test','price_per_spin'=>10,'is_active'=>true]);
        $item=GachaItem::create(['gacha_box_id'=>$box->id,'game_account_id'=>$first->id,'reward_type'=>'game_account','drop_rate'=>12.3456]);
        GachaItem::create(['gacha_box_id'=>$box->id,'game_account_id'=>$assigned->id,'reward_type'=>'game_account','drop_rate'=>0]);
        $key=(string)Str::uuid();
        $result=$this->actingAs($user)->postJson(route('gacha.spin',$box),['request_id'=>$key])->assertOk()->assertJsonPath('account_title','First')->assertJsonPath('next_rewards.0.title','Replacement');
        $next=GachaItem::where('game_account_id',$replacement->id)->firstOrFail();
        $this->assertSame('12.3456',$next->drop_rate);
        $this->assertSame($first->id,$item->fresh()->game_account_id);
        $this->assertSame('0.0000',$item->fresh()->drop_rate);
        $this->assertSame($item->id,GachaSpin::first()->gacha_item_id);
        $this->postJson(route('gacha.spin',$box),['request_id'=>$key])->assertOk()->assertJsonPath('spin_id',$result->json('spin_id'));
        $this->assertSame('90.00',$user->fresh()->balance);
        $this->assertDatabaseCount('gacha_items',3);
        $this->postJson(route('gacha.spin',$box),['request_id'=>(string)Str::uuid()])->assertOk()->assertJsonPath('account_title','Replacement')->assertJsonCount(0,'next_rewards');
        $this->assertSame('available',$foreign->fresh()->status);
        $this->assertSame('available',$assigned->fresh()->status);
        $this->postJson(route('gacha.spin',$box),['request_id'=>(string)Str::uuid()])->assertStatus(422);
        $this->assertSame('80.00',$user->fresh()->balance);
    }
}
