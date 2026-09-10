<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Password};
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    public function request() { return view('auth.forgot-password'); }
    public function email(Request $request)
    {
        $data = $request->validate(['email'=>'required|email|max:254']);
        try {
            Password::sendResetLink($data);
        } catch (\Throwable $exception) {
            Log::warning('Password reset email could not be delivered. Check mail configuration.');
        }
        return back()->with('success','หากอีเมลนี้มีบัญชีในระบบ คุณจะได้รับลิงก์ตั้งรหัสผ่านใหม่ กรุณาตรวจกล่องจดหมายและจดหมายขยะ');
    }
    public function resetForm(Request $request, string $token)
    {
        $request->validate(['email'=>'nullable|email|max:254']);
        return response()->view('auth.reset-password',['token'=>$token,'email'=>$request->query('email','')])
            ->header('Cache-Control','private, no-store')->header('Referrer-Policy','no-referrer');
    }
    public function reset(Request $request)
    {
        $data = $request->validate(['token'=>'required|string','email'=>'required|email|max:254','password'=>'required|string|min:8|max:128|confirmed']);
        $status = Password::reset($data, function (User $user, string $password) {
            $this->savePassword($user,$password);
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email'=>'ลิงก์ไม่ถูกต้อง หมดอายุ หรือถูกใช้แล้ว กรุณาขอลิงก์ใหม่'])->withInput($request->only('email'));
        }
        return redirect()->route('login')->with('success','ตั้งรหัสผ่านใหม่แล้ว กรุณาเข้าสู่ระบบด้วยรหัสผ่านใหม่');
    }
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
