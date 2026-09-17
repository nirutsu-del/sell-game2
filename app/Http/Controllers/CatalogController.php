<?php
namespace App\Http\Controllers;
use App\Models\{Category, Product, GameAccount, PurchaseHistory, ServiceOrder, User};
use Illuminate\Http\Request;
class CatalogController extends Controller {
    public function home() {
        return view('catalog.home', [
            'settings'=>\App\Models\StoreSetting::current(),
            'banners'=>\App\Models\StoreBanner::where('is_active',true)->orderBy('sort_order')->orderBy('id')->get(),
            'categories'=>Category::whereNull('parent_id')->withCount('products','gameAccounts')->orderByDesc('is_featured')->orderBy('sort_order')->take(6)->get(),
            'products'=>config('store.service_catalog_enabled') ? Product::where('is_active',true)->with(['category','variants'=>fn($q)=>$q->where('is_active',true)])->orderByDesc('is_featured')->latest()->take(8)->get() : collect(),
            'accounts'=>GameAccount::with('category')->where('status','available')->latest()->take(8)->get(),
            'featuredBox'=>\App\Models\GachaBox::where('is_active',true)->with('items.account')->orderBy('id')->first(),
            'stats'=>[User::count(),(config('store.service_catalog_enabled') ? Product::where('is_active',true)->count() : 0)+GameAccount::where('status','available')->count(),PurchaseHistory::count()+ServiceOrder::where('status','completed')->count()],
        ]);
    }
    public function index(Request $r, ?Category $category = null) {
        $r->validate(['q'=>'nullable|string|max:100','sort'=>'nullable|in:newest,price_asc,price_desc','available'=>'nullable|in:1']);
        $ids = [];
        if ($category?->exists) {
            $ids = [$category->id]; $frontier = $ids;
            while ($frontier) {
                $frontier = Category::whereIn('parent_id',$frontier)->whereNotIn('id',$ids)->pluck('id')->all();
                $ids = array_merge($ids,$frontier);
            }
        }
        $products = Product::where('is_active',true)->with(['category','variants'=>fn($q)=>$q->where('is_active',true)])
            ->withMin(['variants'=>fn($q)=>$q->where('is_active',true)],'price');
        $accounts = GameAccount::with('category')->where('status','available');
        if (!config('store.service_catalog_enabled')) $products->whereRaw('1 = 0');
        if ($ids) { $products->whereIn('category_id',$ids); $accounts->whereIn('category_id',$ids); }
        if ($r->filled('q')) { $products->where('name','like','%'.$r->q.'%'); $accounts->where('title','like','%'.$r->q.'%'); }
        if ($r->boolean('available')) $products->whereHas('variants',fn($q)=>$q->where('is_active',true)->where(fn($q)=>$q->whereNull('stock')->orWhere('stock','>',0)));
        $direction = $r->sort === 'price_desc' ? 'desc' : 'asc';
        if (in_array($r->sort,['price_asc','price_desc'])) { $products->orderBy('variants_min_price',$direction); $accounts->orderBy('price',$direction); }
        else { $products->latest(); $accounts->latest(); }
        $breadcrumbs = collect(); $cursor = $category;
        while ($cursor?->exists && !$breadcrumbs->contains('id',$cursor->id)) { $breadcrumbs->prepend($cursor); $cursor = $cursor->parent; }
        return view('catalog.index', [
            'category'=>$category, 'breadcrumbs'=>$breadcrumbs,
            'categories'=>Category::where('parent_id',$category?->id)->withCount('products','gameAccounts')->orderBy('sort_order')->get(),
            'products'=>$products->paginate(12,['*'],'products_page')->withQueryString(),
            'accounts'=>$accounts->paginate(12,['*'],'accounts_page')->withQueryString(),
        ]);
    }
    public function show(Product $product) {
        abort_unless($product->is_active,404);
        $product->load(['category','variants'=>fn($q)=>$q->where('is_active',true)->orderBy('price')]);
        return view('catalog.show',compact('product'));
    }
}
