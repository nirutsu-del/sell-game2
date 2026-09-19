<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // Account pages and reset tokens must not be stored by shared caches.
        if ($request->user() || $request->is('login', 'logout', 'register', 'admin', 'admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        // Existing Blade scripts and runtime styles still need inline compatibility.
        $scripts = "'self' 'unsafe-inline'";
        $styles = "'self' 'unsafe-inline'";
        $connections = "'self'";
        if (app()->environment('local') && Vite::isRunningHot()) {
            $url = trim(file_get_contents(Vite::hotFile()));
            $parts = parse_url($url);
            if (filter_var($url, FILTER_VALIDATE_URL) && isset($parts['host'])
                && in_array($parts['scheme'] ?? '', ['http', 'https'], true)) {
                $authority = $parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '');
                $origin = $parts['scheme'].'://'.$authority;
                $websocket = ($parts['scheme'] === 'https' ? 'wss' : 'ws').'://'.$authority;
                $scripts .= ' '.$origin;
                $styles .= ' '.$origin;
                $connections .= ' '.$origin.' '.$websocket;
            }
        }

        return "default-src 'self'; img-src 'self' data: https:; style-src {$styles}; "
            ."script-src {$scripts}; font-src 'self' data:; connect-src {$connections}; "
            ."object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'";
    }
}
