<?php
declare(strict_types=1);

/**
 * Circuit breaker sederhana. Kalau sebuah API eksternal timeout / tidak bisa
 * dihubungi, tandai "down" selama API_DOWN_COOLDOWN detik supaya request
 * berikutnya tidak menunggu timeout lagi.
 */
final class ApiGuard
{
    private static function file(string $name): string
    {
        return CACHE_DIR . '/_down_' . preg_replace('/\W/', '', $name) . '.flag';
    }

    public static function isDown(string $name): bool
    {
        $f = self::file($name);
        return is_file($f) && (time() - filemtime($f)) < API_DOWN_COOLDOWN;
    }

    public static function markDown(string $name): void
    {
        @touch(self::file($name));
    }
}
