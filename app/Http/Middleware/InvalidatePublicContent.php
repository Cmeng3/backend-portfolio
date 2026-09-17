<?php

namespace App\Http\Middleware;

use App\Support\PublicContentCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InvalidatePublicContent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Runs after the content transaction, including relationship-only edits.
        if (! $request->isMethodSafe() && $response->isSuccessful() && ! $request->is('api/v1/admin/logout')) {
            PublicContentCache::invalidate();
        }

        return $response;
    }
}
