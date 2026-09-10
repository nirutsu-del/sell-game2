<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        return view('user.dashboard', [
            'purchases' => $user->purchases()->with('account')->latest()->take(6)->get(),
            'topups' => $user->topups()->latest()->take(6)->get(),
        ]);
    }
}
