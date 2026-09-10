<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TopupTransaction;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TopupController extends Controller
{
    public function history(\Illuminate\Http\Request $request)
    {
        $filters = $request->validate(['action'=>'nullable|in:approved,rejected','reference'=>'nullable|string|max:100']);
        return view('admin.topups.history', ['reviews'=>\App\Models\TopupReview::query()
            ->when($filters['action'] ?? null, fn($query,$action)=>$query->where('action',$action))
            ->when($filters['reference'] ?? null, fn($query,$reference)=>$query->where('reference_no',$reference))
            ->orderByDesc('id')->paginate(30)->withQueryString()]);
    }

    private function recordReview(\Illuminate\Http\Request $request, TopupTransaction $topup, string $action, ?string $reason = null): void
    {
        \App\Models\TopupReview::create([
            'topup_id'=>$topup->id, 'admin_id'=>$request->user()->id, 'admin_name'=>$request->user()->name,
            'reference_no'=>$topup->reference_no, 'amount'=>$topup->amount,
            'action'=>$action, 'from_status'=>'pending', 'to_status'=>$topup->status,
            'reason'=>$reason, 'created_at'=>now(),
        ]);
    }
    public function index(\Illuminate\Http\Request $request)
    {
        $request->validate(['topup'=>'nullable|integer|min:1']);
        return view('admin.topups.index', [
            'topups' => TopupTransaction::with('user')->when($request->filled('topup'),fn($q)=>$q->whereKey($request->integer('topup')))->latest()->paginate(30)->withQueryString(),
        ]);
    }

    public function approve(\Illuminate\Http\Request $request, TopupTransaction $topup)
    {
        DB::transaction(function () use ($topup, $request) {
            $topup = TopupTransaction::with('user')->lockForUpdate()->findOrFail($topup->id);
            if ($topup->status !== 'pending') {
                throw ValidationException::withMessages(['topup' => 'รายการนี้ถูกดำเนินการแล้ว']);
            }

            $verified = $request->validate([
                'funds_received'=>'accepted',
                'received_amount'=>'required|numeric|decimal:0,2|min:1|max:100000',
                'transfer_reference'=>'required|string|min:3|max:100',
            ]);
            if (number_format((float)$verified['received_amount'],2,'.','') !== $topup->amount) {
                throw ValidationException::withMessages(['received_amount'=>'ยอดเข้าจริงไม่ตรงกับยอดที่แจ้ง ห้ามอนุมัติ กรุณาตรวจสอบหรือติดต่อผู้ใช้']);
            }

            $topup->user->increment('balance', $topup->amount);
            $topup->update(['status' => 'success', 'verification_payload'=>[
                'method'=>'manual', 'reviewer_id'=>$request->user()->id,
                'reviewed_at'=>now()->toIso8601String(),
                'received_amount'=>$topup->amount,
                'transfer_reference'=>$verified['transfer_reference'],
            ]]);
            WalletTransaction::create([
                // Review and credit are committed together or rolled back together.
                'user_id' => $topup->user_id,
                'amount' => $topup->amount,
                'type' => 'credit',
                'description' => 'อนุมัติเติมเงิน ' . $topup->reference_no,
                'reference_type' => TopupTransaction::class,
                'reference_id' => $topup->id,
            ]);
            $this->recordReview($request, $topup, 'approved');
            $topup->user->notify(new \App\Notifications\StoreNotification(
                'เติมเงินสำเร็จ', $topup->reference_no.' · เพิ่ม Wallet ฿'.number_format($topup->amount,2), 'topup', $topup->id,
            ));
        });

        return redirect()->route('admin.topups.index', ['topup'=>$topup->id], 303)->with('success', 'อนุมัติรายการและเพิ่มเครดิต Wallet แล้ว');
    }

    public function reject(\Illuminate\Http\Request $request, TopupTransaction $topup)
    {
        DB::transaction(function () use ($topup, $request) {
            $topup = TopupTransaction::with('user')->lockForUpdate()->findOrFail($topup->id);
            if ($topup->status !== 'pending') throw ValidationException::withMessages(['topup'=>'รายการนี้ถูกดำเนินการแล้ว']);
            $data = $request->validate(['reason'=>'required|string|min:5|max:500']);
            $topup->update(['status'=>'failed','verification_payload'=>[
                'method'=>'manual', 'reviewer_id'=>$request->user()->id,
                'reviewed_at'=>now()->toIso8601String(), 'rejection_reason'=>$data['reason'],
            ]]);
            $this->recordReview($request, $topup, 'rejected', $data['reason']);
            $topup->user->notify(new \App\Notifications\StoreNotification(
                'รายการเติมเงินไม่ผ่านการอนุมัติ', $topup->reference_no.' · '.$data['reason'], 'topup', $topup->id,
            ));
        });

        return redirect()->route('admin.topups.index', ['topup'=>$topup->id], 303)->with('success', 'ปฏิเสธรายการและแจ้งเหตุผลให้ผู้ใช้แล้ว');
    }

    public function slip(TopupTransaction $topup)
    {
        abort_unless($topup->slip_path && Storage::disk('local')->exists($topup->slip_path), 404);

        return Storage::disk('local')->response($topup->slip_path);
    }
}
