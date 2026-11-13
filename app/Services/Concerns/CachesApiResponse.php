<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Cache;

/**
 * Cache response API eksternal dengan pola "stale-while-error":
 * 1. Data dianggap fresh selama otaku.api.cache_ttl detik.
 * 2. Setelah melewati TTL, data lama TIDAK langsung dibuang — dipertahankan
 *    hingga otaku.api.cache_retention dan dipakai sebagai fallback bila API gagal.
 * 3. Payload berisi data + fetched_at kecil sehingga freshness cek manual.
 */
trait CachesApiResponse
{
    protected function cacheStore()
    {
        return Cache::store(config('otaku.cache_store'));
    }

    protected function cacheGet(string $key): ?array
    {
        /** @var array{data: mixed, fetched_at: int} $payload */
        $payload = $this->cacheStore()->get($key);

        return is_array($payload) ? $payload : null;
    }

    protected function cachePut(string $key, array $data): void
    {
        $retention = (int) config('otaku.api.cache_retention', 86400);

        $this->cacheStore()->put($key, [
            'data' => $data,
            'fetched_at' => time(),
        ], now()->addSeconds($retention));
    }

    protected function isFresh(array $payload): bool
    {
        $fetchedAt = (int) ($payload['fetched_at'] ?? 0);
        $ttl = (int) config('otaku.api.cache_ttl', 3600);

        return $fetchedAt > 0 && (time() - $fetchedAt) < $ttl;
    }

    /**
     * Data kadaluarsa lebih baik daripada halaman kosong / menunggu.
     */
    protected function staleData(?array $payload): array
    {
        if (is_array($payload['data'] ?? null)) {
            return $payload['data'];
        }

        return [];
    }
}
