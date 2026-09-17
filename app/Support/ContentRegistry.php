<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class ContentRegistry
{
    public static function all(): array
    {
        return config('content');
    }

    public static function get(string $resource): array
    {
        $definition = self::all()[$resource] ?? null;
        abort_unless($definition, 404);

        return $definition;
    }

    public static function query(string $resource): Builder
    {
        $definition = self::get($resource);
        $class = 'App\\Models\\'.$definition['model'];
        $query = $class::query();

        return $query;
    }
}
