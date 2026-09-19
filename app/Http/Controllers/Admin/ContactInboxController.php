<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\ContactConversation;
use App\Services\ContactAttachments;
use App\Notifications\StoreNotification;

class ContactInboxController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status'=>'nullable|in:all,new,read,resolved,staff,customer','q'=>'nullable|string|max:100']);
        $query = ContactMessage::query();
        $status = $request->input('status','all');
        if ($status === 'new') $query->whereNull('read_at')->whereNull('resolved_at');
        if ($status === 'read') $query->whereNotNull('read_at')->whereNull('resolved_at');
        if ($status === 'resolved') $query->whereNotNull('resolved_at');
        if (in_array($status,['staff','customer'],true)) $query->whereNull('resolved_at')->where('waiting_on',$status);
        if ($request->filled('q')) {
            $search = trim($request->q);
            if (preg_match('/^(?:C-)?#?(\d+)$/i',$search,$match)) $query->whereKey((int)$match[1]);
            else $query->where(fn($q)=>$q->where('name','like',"%$search%")->orWhere('email','like',"%$search%")->orWhere('subject','like',"%$search%")->orWhere('message','like',"%$search%")->orWhere('order_reference','like',"%$search%"));
        }
        return response()->view('admin.contacts.index',[
            'messages'=>$query->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString(),
            'counts'=>[
                'all'=>ContactMessage::count(),
                'new'=>ContactMessage::whereNull('read_at')->whereNull('resolved_at')->count(),
                'read'=>ContactMessage::whereNotNull('read_at')->whereNull('resolved_at')->count(),
                'resolved'=>ContactMessage::whereNotNull('resolved_at')->count(),
                'staff'=>ContactMessage::whereNull('resolved_at')->where('waiting_on','staff')->count(),
                'customer'=>ContactMessage::whereNull('resolved_at')->where('waiting_on','customer')->count(),
            ],
        ])->header('Cache-Control','private, no-store');
    }
    public function show(ContactMessage $message)
    {
        return response()->view('admin.contacts.show',['message'=>$message,'replies'=>$message->replies()->with('attachments')->orderBy('id')->paginate(30)])->header('Cache-Control','private, no-store');
    }
    public function reply(Request $request, ContactMessage $message)
    {
        $data = $request->validate(['body'=>'required|string|max:3000']+ContactAttachments::rules());
        ContactConversation::reply($message,$data['body'],$request->user(),true,$request->file('attachments',[]));
        $params = ['message'=>$message->id];
        $lastPage = (int) ceil($message->replies()->count()/30);
        if ($lastPage > 1) $params['page'] = $lastPage;
        return redirect()->route('admin.contacts.show',$params)->with('success','ส่งคำตอบให้ลูกค้าแล้ว');
    }
    public function update(Request $request, ContactMessage $message)
    {
        $data = $request->validate(['action'=>'required|in:read,resolve','resolution_note'=>'nullable|string|max:3000']);
        DB::transaction(function () use ($request,$message,$data) {
            $message = ContactMessage::lockForUpdate()->findOrFail($message->id);
            if ($message->resolved_at) return;
            $message->read_at ??= now();
            if ($data['action'] === 'resolve') {
                $message->resolved_at = now();
                $message->resolved_by = $request->user()->id;
                $message->resolved_by_name = $request->user()->name;
                $message->resolution_note = $data['resolution_note'] ?? null;
                $message->user?->notify(new StoreNotification('เรื่อง C-'.$message->id.' จัดการแล้ว',
                    'หากยังมีปัญหา สามารถตอบกลับเพื่อเปิดเรื่องอีกครั้งได้','contact',$message->id));
            }
            $message->save();
        },3);
        return redirect()->route('admin.contacts.show',$message)->with('success','บันทึกสถานะข้อความแล้ว');
    }
}
