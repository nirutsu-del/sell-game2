<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['filter'=>'nullable|in:all,unread']);
        $query = $request->user()->notifications();
        if ($request->input('filter') === 'unread') $query->whereNull('read_at');
        return response()->view('notifications.index',[
            'notifications'=>$query->orderByDesc('id')->paginate(15)->withQueryString(),
        ])->header('Cache-Control','private, no-store');
    }

    public function count(Request $request)
    {
        return response()->json(['unread'=>$request->user()->unreadNotifications()->count()])
            ->header('Cache-Control','private, no-store');
    }

    public function open(Request $request, string $notification)
    {
        $item = $request->user()->notifications()->findOrFail($notification);
        $data = $item->data;
        $id = (int) ($data['target_id'] ?? 0);
        $adminTargets = ['admin_order','admin_topup','admin_purchase','admin_contact'];
        if (in_array($data['target'] ?? '', $adminTargets)) abort_unless($request->user()->isAdmin(),403);
        // Generate local destinations from known targets; never redirect to stored arbitrary URLs.
        $url = match ($data['target'] ?? '') {
            'order' => route('orders.show',$id),
            'purchase' => route('purchases.show',$id),
            'topup' => route('wallet.index',['topup'=>$id]).'#topup-'.$id,
            'admin_order' => route('admin.service-orders.index',['status'=>'all','q'=>'S-'.$id]),
            'admin_topup' => route('admin.topups.index',['topup'=>$id]),
            'admin_purchase' => route('admin.dashboard',['purchase'=>$id]),
            'admin_contact' => route('admin.contacts.show',$id),
            'contact' => route('contact.show',$id),
            default => route('notifications.index'),
        };
        $item->markAsRead();
        return redirect()->to($url);
    }

    public function readAll(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at'=>now()]);
        return back()->with('success','ทำเครื่องหมายว่าอ่านทั้งหมดแล้ว');
    }
}
