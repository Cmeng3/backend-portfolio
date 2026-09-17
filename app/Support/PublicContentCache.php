<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PublicContentCache
{
    public static function namespace(): string
    {
        return 'portfolio-public:'.hash('sha256', json_encode([
            config('database.default'), config('database.connections.pgsql.host'),
            config('database.connections.pgsql.database'), config('database.connections.pgsql.url'),
        ]));
    }

    public static function version(): string
    {
        return (string) Cache::get(self::namespace().':version', 'initial');
    }

    public static function invalidate(): void
    {
        Cache::forever(self::namespace().':version', Str::uuid()->toString());
    }
}
