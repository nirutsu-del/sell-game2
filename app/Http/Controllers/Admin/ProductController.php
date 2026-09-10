<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Product, Category, ServiceOrder};
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ProductController extends Controller {
    public function index() { return view('admin.products.index',['products'=>Product::with('category','variants')->latest()->paginate(20)]); }
    public function create() { return view('admin.products.form',['product'=>new Product(),'categories'=>Category::orderBy('name')->get()]); }
    public function edit(Product $product) { return view('admin.products.form',['product'=>$product->load('variants'),'categories'=>Category::orderBy('name')->get()]); }
    public function store(Request $r) { return $this->save($r,new Product()); }
    public function update(Request $r, Product $product) { return $this->save($r,$product); }
    private function save(Request $r, Product $product) {
        $data = $r->validate([
            'name'=>'required|string|max:150','category_id'=>'required|exists:categories,id',
            'description'=>'nullable|string|max:10000','terms'=>'nullable|string|max:5000',
            'recipient_label'=>'required|string|max:100','image'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'is_active'=>'nullable|boolean','is_featured'=>'nullable|boolean',
            'variants'=>'required|array|min:1|max:30','variants.*.id'=>'nullable|integer|distinct',
            'variants.*.name'=>'required|string|max:100','variants.*.price'=>'required|numeric|min:1|max:1000000',
            'variants.*.stock'=>'nullable|integer|min:0|max:1000000','variants.*.is_active'=>'nullable|boolean',
        ]);
        DB::transaction(function () use ($r,$data,$product) {
            if ($product->exists) $product = Product::lockForUpdate()->findOrFail($product->id);
            $attributes = collect($data)->except(['variants','image'])->all();
            $attributes['is_active'] = $r->boolean('is_active'); $attributes['is_featured'] = $r->boolean('is_featured');
            if ($r->hasFile('image')) $attributes['image'] = $r->file('image')->store('products','public');
            $product->fill($attributes)->save();
            foreach ($data['variants'] as $v) {
                $attributes = ['name'=>$v['name'],'price'=>$v['price'],'stock'=>$v['stock'] ?? null,'is_active'=>(bool)($v['is_active'] ?? false)];
                if (!empty($v['id'])) $product->variants()->findOrFail($v['id'])->update($attributes);
                else $product->variants()->create($attributes);
            }
        });
        return redirect()->route('admin.products.index')->with('success','บันทึกสินค้าและแพ็กเกจแล้ว');
    }
    public function orders(Request $r) {
        $r->validate(['status'=>'nullable|in:all,pending,processing,completed,refunded','q'=>'nullable|string|max:100','waiting'=>'nullable|in:1','sort'=>'nullable|in:oldest,newest']);
        $query = ServiceOrder::with('user','events');
        $status = $r->input('status','pending');
        if ($status !== 'all') $query->where('status',$status);
        if ($r->filled('q')) {
            $search = trim($r->q);
            if (preg_match('/^(?:S-)?#?(\d+)$/i',$search,$match)) $query->whereKey((int)$match[1]);
            else $query->where(fn($q)=>$q->where('product_name','like',"%$search%")->orWhereHas('user',fn($u)=>$u->where('name','like',"%$search%")));
        }
        if ($r->boolean('waiting')) $query->whereIn('status',['pending','processing'])->where('created_at','<=',now()->subDay());
        $query->orderBy('created_at',$r->input('sort','oldest') === 'newest' ? 'desc' : 'asc')->orderBy('id');
        return view('admin.products.orders',[
            'orders'=>$query->paginate(20)->withQueryString(),'selectedStatus'=>$status,
            'counts'=>ServiceOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total','status'),
            'waitingCount'=>ServiceOrder::whereIn('status',['pending','processing'])->where('created_at','<=',now()->subDay())->count(),
        ]);
    }
    public function updateOrder(Request $r, ServiceOrder $order, ServiceOrderService $service) {
        $data = $r->validate(['status'=>'required|in:processing,completed,refunded','delivery_note'=>'required|string|max:5000','expected_status'=>'nullable|in:pending,processing']);
        $service->updateStatus($order,$data['status'],$data['delivery_note'],$r->user(),$data['expected_status'] ?? null);
        return back()->with('success','อัปเดตคำสั่งซื้อแล้ว');
    }
}
