<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\RateLimiter::for('login', function (\Illuminate\Http\Request $request) {
            $input = $request->input('email');
            $email = is_string($input) ? strtolower(trim($input)) : '';
            return [
                \Illuminate\Cache\RateLimiting\Limit::perMinute(30)->by('login-ip:'.$request->ip()),
                \Illuminate\Cache\RateLimiting\Limit::perMinute(5)->by('login-account:'.hash('sha256', $email.'|'.$request->ip())),
            ];
        });
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function ($user,$token) {
            $url = rtrim(config('app.url'),'/').route('password.reset',['token'=>$token,'email'=>$user->getEmailForPasswordReset()],false);
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('ตั้งรหัสผ่านใหม่')
                ->greeting('สวัสดี')
                ->line('เราได้รับคำขอตั้งรหัสผ่านใหม่สำหรับบัญชีของคุณ')
                ->action('ตั้งรหัสผ่านใหม่',$url)
                ->line('ลิงก์นี้หมดอายุใน '.config('auth.passwords.users.expire').' นาที และใช้ได้ครั้งเดียว')
                ->line('หากคุณไม่ได้ขอเปลี่ยนรหัสผ่าน ไม่ต้องดำเนินการใด ๆ');
        });
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $view->with('storeSettings', \App\Models\StoreSetting::current());
            $view->with('notificationUnread', auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
        });
    }
}
