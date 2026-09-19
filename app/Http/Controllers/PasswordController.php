<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Password};
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function edit() { return view('auth.change-password'); }
    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password'=>'required|current_password',
            'password'=>'required|string|min:8|max:128|confirmed|different:current_password',
        ],['current_password.current_password'=>'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
        $this->savePassword($request->user(),$data['password']);
        Password::deleteToken($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login')->with('success','เปลี่ยนรหัสผ่านแล้ว กรุณาเข้าสู่ระบบใหม่');
    }
    private function savePassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user,$password) {
            $user->forceFill(['password'=>Hash::make($password),'remember_token'=>Str::random(60)])->save();
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table','sessions'))->where('user_id',$user->id)->delete();
            }
        });
    }
}
