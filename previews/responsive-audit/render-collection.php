<?php
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\URL::forceRootUrl('http://localhost/sell-game2/public');
$user=new App\Models\User(['name'=>'Mizuki Collector','balance'=>0]);$user->id=999999;
Illuminate\Support\Facades\Auth::setUser($user);
view()->share('errors',new Illuminate\Support\ViewErrorBag());
$accounts=App\Models\GameAccount::whereIn('title',array_column(config('demo_catalog'),'title'))->with('category')->take(6)->get();
$owned=$accounts->map(function($account,$i){$p=new App\Models\PurchaseHistory(['source'=>$i%2?'gacha':'shop']);$p->id=9900+$i;$p->created_at=now();$p->setRelation('account',$account);return $p;});
$games=$accounts->pluck('category')->unique('id');
file_put_contents(__DIR__.'/collection-preview.html',view('user.collection',['owned'=>$owned,'items'=>$owned,'games'=>$games])->render());
file_put_contents(__DIR__.'/collection-empty-preview.html',view('user.collection',['owned'=>collect(),'items'=>collect(),'games'=>collect()])->render());
echo 'Rendered sample previews without saving users or purchases';

session()->put('reveal_purchase_id',$owned->first()->id);
file_put_contents(__DIR__.'/reveal-preview.html',view('purchases.show',['purchase'=>$owned->first(),'credentials'=>['username'=>'demo-preview','password'=>'DEMO-ONLY']])->render());
$user->balance=1000;
$box=App\Models\GachaBox::with('items.account')->first();
file_put_contents(__DIR__.'/gacha-modes-preview.html',view('gacha.show',['box'=>$box,'recentSpins'=>collect()])->render());
$box->load('items.account.category');
$availableAccounts=App\Models\GameAccount::with('category')->where('status','available')->whereNotIn('id',$box->items->pluck('game_account_id')->filter())->get();
file_put_contents(__DIR__.'/reward-admin-preview.html',view('admin.gacha.items',['box'=>$box,'availableAccounts'=>$availableAccounts,'totalRate'=>$box->items->sum('drop_rate')])->render());
