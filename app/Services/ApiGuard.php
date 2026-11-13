<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Circuit breaker sederhana. Kalau sebuah API eksternal timeout / tidak bisa
 * dihubungi, tandai "down" selama cooldown (default 60 detik) supaya request
 * berikutnya tidak menunggu timeout lagi.
 */
final class ApiGuard
{
    public static function isDown(string $name): bool
    {
        return self::store()->has(self::key($name));
    }

    public static function markDown(string $name): void
    {
        self::store()->put(
            self::key($name),
            now()->timestamp,
            now()->addSeconds((int) config('otaku.api.down_cooldown', 60))
        );
    }

    private static function store()
    {
        return Cache::store(config('otaku.cache_store'));
    }

    private static function key(string $name): string
    {
        $prefix = (string) config('otaku.cache_prefix', 'otaku');

        return $prefix.':down:'.preg_replace('/\W/', '', strtolower($name));
    }
}
