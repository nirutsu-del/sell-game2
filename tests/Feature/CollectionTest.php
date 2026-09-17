<?php
namespace Tests\Feature;
use App\Models\{User,Category,GameAccount,PurchaseHistory};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class CollectionTest extends TestCase {
    use RefreshDatabase;
    public function test_collection_is_private_filterable_and_does_not_expose_credentials(): void {
        $owner=User::factory()->create(); $other=User::factory()->create();
        $first=Category::create(['name'=>'Game A','slug'=>'game-a']);
        $second=Category::create(['name'=>'Game B','slug'=>'game-b']);
        foreach([[$owner,$first,'My first card','shop'],[$owner,$second,'My gacha card','gacha'],[$other,$first,'Private other card','shop']] as [$user,$category,$title,$source]) {
            $account=GameAccount::create(['category_id'=>$category->id,'title'=>$title,'price'=>100,'status'=>'sold','credentials_data'=>['password'=>'PRIVATE_SECRET']]);
            PurchaseHistory::create(['user_id'=>$user->id,'game_account_id'=>$account->id,'price_paid'=>100,'source'=>$source,'account_data_delivered'=>'PRIVATE_SECRET']);
        }
        $this->get(route('user.collection'))->assertRedirect(route('login'));
        $this->actingAs($owner)->get(route('user.collection'))->assertOk()->assertSee('My first card')->assertSee('My gacha card')->assertDontSee('Private other card')->assertDontSee('PRIVATE_SECRET');
        $this->get(route('user.collection',['game'=>$first->id]))->assertOk()->assertSee('My first card')->assertDontSee('My gacha card');
        $this->get(route('user.collection',['game'=>999]))->assertOk()->assertSee('ยังไม่มีไอดีในเกมที่เลือก');
    }
    public function test_new_user_sees_empty_collection(): void {
        $this->actingAs(User::factory()->create())->get(route('user.collection'))->assertOk()->assertSee('ชั้นสะสมกำลังรอไอดีแรกของคุณ');
    }
}
