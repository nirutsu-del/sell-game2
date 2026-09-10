<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class EnsureServiceCatalogEnabled {
    public function handle(Request $request, Closure $next): Response {
        abort_unless(config('store.service_catalog_enabled'), 404);
        return $next($request);
    }
}
