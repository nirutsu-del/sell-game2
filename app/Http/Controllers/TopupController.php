<?php
namespace App\Http\Controllers;

use App\Models\TopupTransaction;
use App\Services\StoreNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TopupController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['topup'=>'nullable|integer|min:1']);
        $settings = \App\Models\StoreSetting::current();
        return view('wallet.index', [
            'receipt' => $request->filled('topup') ? $request->user()->topups()->findOrFail($request->integer('topup')) : null,
            'requestId' => Str::isUuid($request->old('request_id', '')) ? $request->old('request_id') : (string) Str::uuid(),
            'transactions'=>$request->user()->topups()->when($request->filled('topup'),fn($q)=>$q->whereKey($request->integer('topup')))->latest()->paginate(15)->withQueryString(),
            'promptpayQrImage'=>$settings->qrUrl('promptpay'),
            'truemoneyQrImage'=>$settings->qrUrl('truemoney'),
            'paymentSettings'=>$settings,
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate(['request_id'=>'required|uuid','amount'=>'required|numeric|decimal:0,2|min:1|max:100000','payment_method'=>'required|in:promptpay_slip,truemoney_gift','slip'=>'nullable|image|max:5120']);
        $slipHash = $request->hasFile('slip') ? hash_file('sha256', $request->file('slip')->getRealPath()) : null;
        $fingerprint = hash('sha256', json_encode([
            number_format((float) $data['amount'], 2, '.', ''), $data['payment_method'],
            $slipHash,
        ], JSON_THROW_ON_ERROR));
        $path = null;
        try {
        $topup = DB::transaction(function () use ($request,$data,$fingerprint,$slipHash,&$path) {
            // Serialize requests for this user; the unique index is the final safeguard.
            \App\Models\User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $previous = $request->user()->topups()->where('request_id', strtolower($data['request_id']))->first();
            if ($previous) {
                if (!hash_equals($previous->request_fingerprint, $fingerprint)) {
                    throw ValidationException::withMessages(['request_id'=>'คำขอนี้ถูกส่งแล้วแต่ข้อมูลไม่ตรงกัน กรุณาดูประวัติหรือเริ่มรายการใหม่']);
                }
                return $previous;
            }
            // A database primary key also protects against concurrent uploads across users.
            if ($slipHash && !DB::table('topup_slip_hashes')->insertOrIgnore(['hash'=>$slipHash])) {
                throw ValidationException::withMessages(['slip'=>'ไฟล์สลิปนี้เคยใช้แล้ว กรุณาตรวจประวัติหรือติดต่อร้าน ไม่ต้องส่งซ้ำ']);
            }
            $path = $request->hasFile('slip') ? $request->file('slip')->store('slips','local') : null;
            if ($path === false) throw new \RuntimeException('Could not store payment slip.');
            $topup = TopupTransaction::create([
                'amount'=>$data['amount'],'payment_method'=>$data['payment_method'],'user_id'=>$request->user()->id,
                'reference_no'=>Str::upper(Str::random(16)),'slip_path'=>$path,
            ]);
            $topup->forceFill(['request_id'=>strtolower($data['request_id']), 'request_fingerprint'=>$fingerprint])->save();
            if ($slipHash) DB::table('topup_slip_hashes')->where('hash',$slipHash)->update(['topup_id'=>$topup->id]);
            StoreNotifier::admins('มีรายการเติมเงินใหม่', $topup->reference_no.' · ฿'.number_format($topup->amount,2), 'admin_topup', $topup->id);
            return $topup;
        });
        } catch (\Throwable $error) {
            if ($path) Storage::disk('local')->delete($path);
            throw $error;
        }
        return redirect()->route('wallet.index', ['topup'=>$topup->id], 303)
            ->with('success','บันทึกรายการแล้ว ดูสถานะล่าสุดด้านล่าง ไม่ต้องส่งซ้ำ');
    }
}
