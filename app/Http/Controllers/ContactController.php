<?php
namespace App\Http\Controllers;
use App\Models\{ContactMessage, StoreSetting};
use App\Services\StoreNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use App\Services\ContactConversation;
class ContactController extends Controller {
    public function index(Request $request) {
        return response()->view('contacts.index', ['messages'=>ContactMessage::where('user_id',$request->user()->id)->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)])
            ->header('Cache-Control','private, no-store');
    }
    private function authorizeMessage(Request $request, ContactMessage $message): void {
        if ($request->routeIs('contact.guest.*')) abort_unless($message->user_id === null,404);
        else abort_unless($message->user_id !== null && (int)$message->user_id === (int)$request->user()->id,404);
    }
    public function show(Request $request, ContactMessage $message) {
        $this->authorizeMessage($request,$message);
        $guest = $request->routeIs('contact.guest.*');
        return response()->view('contacts.show',[
            'message'=>$message,
            'replies'=>$message->replies()->orderBy('id')->paginate(30),
            'trackingUrl'=>$guest ? URL::signedRoute('contact.guest.show',$message) : null,
            'replyUrl'=>$guest ? URL::signedRoute('contact.guest.reply',$message) : route('contact.reply',$message),
        ])->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer');
    }
    public function reply(Request $request, ContactMessage $message) {
        $this->authorizeMessage($request,$message);
        $data = $request->validate(['body'=>'required|string|max:3000']);
        ContactConversation::reply($message,$data['body'],$message->user_id ? $request->user() : null,false);
        $params = ['message'=>$message->id];
        $lastPage = (int) ceil($message->replies()->count()/30);
        if ($lastPage > 1) $params['page'] = $lastPage;
        $url = $request->routeIs('contact.guest.*') ? URL::signedRoute('contact.guest.show',$params) : route('contact.show',$params);
        return redirect($url)->with('success','ส่งข้อความแล้ว ร้านจะตอบกลับในเรื่องนี้');
    }
    public function create() { return view('contact',['settings'=>StoreSetting::current()]); }
    public function store(Request $request) {
        $data = $request->validate(['name'=>'required|string|max:100','email'=>'required|email|max:254','subject'=>'required|string|max:150','message'=>'required|string|max:3000']);
        $message = DB::transaction(function () use ($request,$data) {
            $message = ContactMessage::create($data+['user_id'=>$request->user()?->id]);
            StoreNotifier::admins('มีข้อความติดต่อใหม่ C-'.$message->id, Str::limit($message->subject,120), 'admin_contact',$message->id);
            return $message;
        });
        $url = $message->user_id ? route('contact.show',$message) : URL::signedRoute('contact.guest.show',$message);
        if (!$message->user_id) $request->session()->put('contact_tracking_url',$url);
        return redirect($url)->with('success','ส่งข้อความถึงร้านแล้ว เลขอ้างอิง C-'.$message->id);
    }
}
