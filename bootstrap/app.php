<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->alias(['admin' => \App\Http\Middleware\EnsureAdmin::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $exception, \Illuminate\Http\Request $request) {
            // Form errors must return to their own page, even after background polling.
            $destination = match ($request->route()?->getName()) {
                'admin.gacha.store' => route('admin.gacha.create'),
                'admin.gacha.update' => route('admin.gacha.edit', $request->route('gacha')),
                'admin.gacha.items.add', 'admin.gacha.items.rates' => route('admin.gacha.items', $request->route('gacha')),
                'gacha.spin' => route('gacha.show', $request->route('box')),
                default => null,
            };
            if ($destination !== null) {
                $exception->redirectTo($destination);
            }
            // Keep Laravel's normal error bags, old input, and JSON 422 responses.
            return null;
        });
    })->create();
