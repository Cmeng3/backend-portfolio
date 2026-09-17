<?php

namespace App\Http\Middleware;

use App\Support\PublicContentCache;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CachePublicContent
{
    public function handle(Request $request, Closure $next): Response
    {
        $ttl = max(0, min(300, (int) config('portfolio.public_cache_seconds', 60)));
        if ($ttl === 0 || ! $request->isMethod('GET')) {
            return $next($request);
        }
        $key = PublicContentCache::namespace().':'.PublicContentCache::version().':'.hash('sha256', $request->fullUrl());
        $cached = Cache::get($key);
        if ($cached !== null) {
            return response($cached, 200, ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store', 'X-Portfolio-Cache' => 'HIT']);
        }
        $response = $next($request);
        if ($response->getStatusCode() === 200 && str_contains((string) $response->headers->get('Content-Type'), 'application/json')) {
            Cache::put($key, $response->getContent(), $ttl);
            $response->headers->set('Cache-Control', 'no-store');
            $response->headers->set('X-Portfolio-Cache', 'MISS');
        }

        return $response;
    }
}
