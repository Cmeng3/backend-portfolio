<?php

namespace App\Providers;

use App\Support\PublicContentCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $models = array_unique(array_column(config('content'), 'model'));
        $models[] = 'Media';
        foreach ($models as $model) {
            $class = 'App\\Models\\'.$model;
            $class::saved(fn () => PublicContentCache::invalidate());
            $class::deleted(fn () => PublicContentCache::invalidate());
        }
        RateLimiter::for('admin-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
    }
}
