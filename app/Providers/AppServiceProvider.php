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
        \Illuminate\Support\Facades\View::composer('layouts.app', function ($view) {
            $view->with('storeSettings', \App\Models\StoreSetting::current());
            $view->with('notificationUnread', auth()->check() ? auth()->user()->unreadNotifications()->count() : 0);
        });
    }
}
