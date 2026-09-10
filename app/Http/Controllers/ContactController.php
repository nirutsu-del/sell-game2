<?php
namespace App\Http\Controllers;
use App\Models\{ContactMessage, StoreSetting};
use App\Services\StoreNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ContactController extends Controller {
    public function create() { return view('contact',['settings'=>StoreSetting::current()]); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required|string|max:100','email'=>'required|email|max:254','subject'=>'required|string|max:150','message'=>'required|string|max:3000']);
        $message = DB::transaction(function () use ($request,$data) {
            $message = ContactMessage::create($data+['user_id'=>$request->user()?->id]);
            StoreNotifier::admins('มีข้อความติดต่อใหม่ C-'.$message->id, Str::limit($message->subject,120), 'admin_contact',$message->id);
            return $message;
        });
        return back()->with('success','ส่งข้อความถึงร้านแล้ว เลขอ้างอิง C-'.$message->id);
    }
}
