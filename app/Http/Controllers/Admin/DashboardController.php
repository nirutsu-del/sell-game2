<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{GameAccount, PurchaseHistory, TopupTransaction, User};

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $request->validate(['purchase'=>'nullable|integer|min:1']);
        $reporter = app(\App\Services\SalesReportService::class);
        $all = $reporter->summary(['start'=>null,'end'=>null]);
        $today = $reporter->summary($reporter->period(new \Illuminate\Http\Request(['period'=>'today'])));
        return view('admin.dashboard', [
            'stats' => [
                'sales' => $all['gross']/100,
                'salesToday' => $today['gross']/100,
                'availableAccounts' => GameAccount::where('status', 'available')->count(),
                'soldAccounts' => GameAccount::where('status', 'sold')->count(),
                'pendingTopups' => TopupTransaction::where('status', 'pending')->count(),
                'walletBalance' => User::sum('balance'),
            ],
            'recentPurchases' => PurchaseHistory::with(['account', 'user'])->when($request->filled('purchase'),fn($q)=>$q->whereKey($request->integer('purchase')))->latest()->take(8)->get(),
            'pendingTopups' => TopupTransaction::with('user')->where('status', 'pending')->latest()->take(8)->get(),
        ]);
    }
}
