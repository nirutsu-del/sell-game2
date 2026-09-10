<?php
namespace App\Http\Controllers;
use App\Models\User; use Illuminate\Http\Request; use Illuminate\Support\Facades\Auth;
class AuthController extends Controller {
 public function form(){return view('auth.form');}
 public function logoutForm(){return view('auth.logout');}
 public function register(Request $r){$d=$r->validate(['name'=>'required|string|max:100','email'=>'required|email|unique:users','password'=>'required|string|min:8|confirmed']);$user=User::create($d);Auth::login($user);$r->session()->regenerate();return redirect()->route('user.dashboard');}
 public function login(Request $r){$d=$r->validate(['email'=>'required|email','password'=>'required']);if(!Auth::attempt($d,$r->boolean('remember')))return back()->withErrors(['email'=>'อีเมลหรือรหัสผ่านไม่ถูกต้อง'])->onlyInput('email');$r->session()->regenerate();return redirect()->intended($r->user()->isAdmin() ? route('admin.dashboard') : route('user.dashboard'));}
 public function logout(Request $r){Auth::logout();$r->session()->invalidate();$r->session()->regenerateToken();return redirect()->route('shop.index')->with('success','ออกจากระบบเรียบร้อยแล้ว');}
}
