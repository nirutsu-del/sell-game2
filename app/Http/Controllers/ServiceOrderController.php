<?php
namespace App\Http\Controllers;
use App\Models\{Product, ServiceOrder};
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
class ServiceOrderController extends Controller {
    public function store(Request $request, Product $product, ServiceOrderService $service) {
        $data = $request->validate(['variant_id'=>'required|integer','quantity'=>'required|integer|min:1|max:100','recipient'=>'required|string|max:500','request_id'=>'required|uuid','accept_terms'=>'accepted']);
        $order = $service->place($request->user(),$product,$data);
        return redirect()->route('orders.show',$order)->with('success','สั่งซื้อสำเร็จ ร้านได้รับรายการของคุณแล้ว');
    }
    public function index(Request $request) {
        $filters = $request->validate(['type'=>'nullable|in:all,service,account,gacha','q'=>'nullable|string|max:100','status'=>'nullable|in:pending,processing,completed,refunded']);
        return view('orders.index',['orders'=>app(\App\Services\OrderHistoryService::class)->paginate($request->user(),$filters)]);
    }
    public function show(Request $request, ServiceOrder $order) {
        abort_unless($order->user_id === $request->user()->id,404);
        $order->load('events');
        return view('orders.show',compact('order'));
    }
}
