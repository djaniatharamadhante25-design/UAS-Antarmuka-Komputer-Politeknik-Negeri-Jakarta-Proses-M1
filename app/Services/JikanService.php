<?php

namespace App\Services;

use App\Services\Concerns\CachesApiResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

final class JikanService
{
    use CachesApiResponse;

    private AniListService $aniList;

    private string $baseUrl;

    private ?string $lastError = null;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('otaku.jikan.base_url')), '/');
        $this->aniList = new AniListService;
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    public function aniListService(): AniListService
    {
        return $this->aniList;
    }

    public function popularAnime(int $limit = 12): array
    {
        return $this->cached('home_popular_'.min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?'.http_build_query(['limit' => min(12, $limit), 'sfw' => 'true']));
            $data = $j['data'] ?? [];
            if ($data) {
                return $data;
            }

            return $this->aniList->popularAnime($limit);
        });
    }

    public function topAnime(int $limit = 6): array
    {
        return $this->cached('v8_home_top_score_'.min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?'.http_build_query(['filter' => 'bypopularity', 'limit' => min(12, $limit), 'sfw' => 'true']));
            $data = $j['data'] ?? [];
            if ($data) {
                usort($data, fn ($a, $b) => (float) ($b['score'] ?? 0) <=> (float) ($a['score'] ?? 0));

                return array_slice($data, 0, min(12, $limit));
            }

            return $this->aniList->topAnime($limit);
        });
    }

    public function popularManga(int $limit = 12): array
    {
        return $this->cached('home_popular_manga_'.min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/manga?'.http_build_query(['filter' => 'bypopularity', 'limit' => min(12, $limit)]));
            $data = $j['data'] ?? [];

            return $data ?: $this->aniList->popularManga($limit);
        });
    }

    public function browse(string $type, array $filters = [], int $page = 1, int $perPage = 28): array
    {
        // Untuk halaman Explore, AniList jadi sumber utama karena mendukung
        // multi-filter browsing dengan pagination.
        $ani = $this->aniList->browse($type, $filters, $page, $perPage);
        if ($ani) {
            return $ani;
        }

        $endpoint = $type === 'manga' ? '/manga' : '/anime';
        $params = ['limit' => min(25, $perPage), 'page' => $page, 'sfw' => 'true'];
        foreach (['type', 'status', 'rating', 'min_score', 'order_by', 'sort', 'genres', 'start_date', 'end_date'] as $key) {
            if (($filters[$key] ?? '') !== '' && $filters[$key] !== null) {
                $params[$key] = $filters[$key];
            }
        }
        $j = $this->request($endpoint.'?'.http_build_query($params));

        return $j['data'] ?? [];
    }

    public function searchAnime(string $query, array $filters = [], int $page = 1, int $perPage = 28): array
    {
        return $this->search('/anime', $query, $filters, 'ANIME', $page, $perPage);
    }

    public function searchManga(string $query, array $filters = [], int $page = 1, int $perPage = 28): array
    {
        return $this->search('/manga', $query, $filters, 'MANGA', $page, $perPage);
    }

    public function anime(int $id): ?array
    {
        return $this->detailMerged('ANIME', $id);
    }

    public function manga(int $id): ?array
    {
        return $this->detailMerged('MANGA', $id);
    }

    /**
     * Resolver tunggal untuk semua poster. Jikan/MAL memberi detail MAL yang luas,
     * AniList memperkaya judul yang sama dengan tag, karakter + VA, staff, studios,
     * relations, external links, streaming dan recommendations.
     * Bila salah satu service sedang unavailable, sisi lain tetap bisa dipakai.
     */
    private function detailMerged(string $type, int $malId): ?array
    {
        if ($malId < 1) {
            return null;
        }

        $kind = $type === 'MANGA' ? 'manga' : 'anime';
        $base = $this->request('/'.$kind.'/'.$malId.'/full')['data'] ?? null;
        if (! $base) {
            $base = $this->request('/'.$kind.'/'.$malId)['data'] ?? null;
        }
        if (! $base) {
            return null;
        }

        $base['mal_id'] = (int) ($base['mal_id'] ?? $malId);
        $base['_source'] = 'Jikan / MyAnimeList';

        // Bagian tambahan diambil PARALEL. Kalau salah satu gagal, detail utama tetap tampil.
        $extra = $this->requestMany([
            'characters' => '/'.$kind.'/'.$malId.'/characters',
            'staff' => '/'.$kind.'/'.$malId.'/staff',
            'recommendations' => '/'.$kind.'/'.$malId.'/recommendations',
        ]);

        $castPayload = $extra['characters']['data'] ?? [];
        if (is_array($castPayload) && array_key_exists('characters', $castPayload)) {
            $base['characters'] = $castPayload['characters'] ?? [];
            $base['staff'] = $castPayload['staff'] ?? [];
        } elseif ($castPayload) {
            $base['characters'] = $castPayload;
        }

        $staffResponse = $extra['staff']['data'] ?? [];
        if ($staffResponse) {
            $base['staff'] = $staffResponse;
        }

        $recommendations = $extra['recommendations']['data'] ?? [];
        if ($recommendations) {
            $base['recommendations'] = $recommendations;
        }

        return $base;
    }

    public function detailByAniListId(string $type, int $id): ?array
    {
        return $this->aniList->detailByAniListId($type, $id);
    }

    public function recommendations(int $id, string $type = 'anime'): array
    {
        if ($id < 1) {
            return [];
        }
        $type = $type === 'manga' ? 'manga' : 'anime';
        $aniType = strtoupper($type);
        $detail = $this->aniList->detailByMalId($aniType, $id);
        $ani = [];
        foreach (($detail['recommendations']['nodes'] ?? []) as $node) {
            $rec = $node['mediaRecommendation'] ?? null;
            if ($rec) {
                $ani[] = $rec;
            }
        }
        if ($ani) {
            return $ani;
        }

        $j = $this->request('/'.$type.'/'.$id.'/recommendations');

        return array_values(array_filter(array_map(function ($item) {
            return $item['entry'] ?? $item;
        }, $j['data'] ?? [])));
    }

    private function search(string $endpoint, string $query, array $filters, string $aniType, int $page = 1, int $perPage = 28): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        $params = ['q' => $query, 'page' => $page, 'limit' => min(25, $perPage), 'sfw' => 'true'];
        foreach (['type', 'status', 'rating', 'min_score', 'order_by', 'sort', 'genres', 'start_date', 'end_date'] as $key) {
            if (($filters[$key] ?? '') !== '' && ($filters[$key] ?? null) !== null) {
                $params[$key] = $filters[$key];
            }
        }
        $json = $this->request($endpoint.'?'.http_build_query($params));
        $data = $json['data'] ?? [];
        if ($data) {
            return $this->rank($data, $query);
        }

        $fallback = $this->aniList->search($aniType, $query, $page, $perPage);

        return $this->rank($fallback, $query);
    }

    private function rank(array $items, string $query): array
    {
        $q = $this->normalize($query);
        $tokens = array_values(array_filter(explode(' ', $q)));
        foreach ($items as &$item) {
            $titles = [];
            foreach ($item['titles'] ?? [] as $t) {
                if (! empty($t['title'])) {
                    $titles[] = $t['title'];
                }
            }
            foreach (['title', 'title_english', 'title_japanese'] as $key) {
                if (! empty($item[$key])) {
                    $titles[] = $item[$key];
                }
            }
            $best = 0;
            foreach (array_unique(array_map(fn ($v) => $this->normalize((string) $v), $titles)) as $title) {
                if ($title === '') {
                    continue;
                }
                if ($title === $q) {
                    $score = 1000;
                } elseif (str_starts_with($title, $q)) {
                    $score = 850;
                } elseif (str_contains($title, $q)) {
                    $score = 700;
                } else {
                    $matched = 0;
                    foreach ($tokens as $token) {
                        if (str_contains($title, $token)) {
                            $matched++;
                        }
                    }
                    $score = $tokens ? ($matched / count($tokens)) * 500 : 0;
                }
                $best = max($best, $score);
            }
            $item['_relevance'] = $best;
        }
        unset($item);
        usort($items, fn ($a, $b) => ($b['_relevance'] ?? 0) <=> ($a['_relevance'] ?? 0));

        return array_values(array_filter(array_slice($items, 0, 25), fn ($a) => (($a['_relevance'] ?? 0) >= 200)));
    }

    private function normalize(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtolower($s)));
    }

    private function request(string $path): array
    {
        $this->lastError = null;
        $key = config('otaku.cache_prefix').':jikan:'.sha1($path);
        $cached = $this->cacheGet($key);

        if (is_array($cached) && $this->isFresh($cached)) {
            return $cached['data'];
        }

        // Circuit breaker: kalau API baru saja timeout/gagal konek, jangan menunggu lagi.
        if (ApiGuard::isDown('jikan')) {
            $this->lastError = 'Jikan sedang tidak terjangkau (dilewati sementara).';

            return $this->staleData($cached);
        }

        try {
            $response = $this->client()->get($this->baseUrl.$path);
        } catch (ConnectionException $e) {
            $this->lastError = 'Jikan: '.($e->getMessage() ?: 'request gagal');
            ApiGuard::markDown('jikan');

            return $this->staleData($cached);
        }

        if (! $response->successful()) {
            $this->lastError = 'Jikan HTTP '.$response->status();

            return $this->staleData($cached);
        }

        $data = $response->json();
        if (! is_array($data)) {
            $this->lastError = 'Jikan response bukan JSON valid.';

            return $this->staleData($cached);
        }

        $this->cachePut($key, $data);

        return $data;
    }

    private function client()
    {
        return Http::acceptJson()
            ->connectTimeout((int) config('otaku.api.connect_timeout'))
            ->timeout((int) config('otaku.api.timeout'))
            ->withUserAgent(config('otaku.jikan.user_agent'));
    }

    /**
     * Ambil beberapa endpoint Jikan sekaligus (paralel via Http::pool).
     *
     * @param  array<string, string>  $paths  key => path
     * @return array<string, array>
     */
    private function requestMany(array $paths): array
    {
        $out = [];
        $pending = [];
        foreach ($paths as $key => $path) {
            $key2 = config('otaku.cache_prefix').':jikan:'.sha1($path);
            $cached = $this->cacheGet($key2);
            if (is_array($cached) && $this->isFresh($cached)) {
                $out[$key] = $cached['data'];
            } else {
                $pending[$key] = [$path, $key2, $cached];
            }
        }
        if (! $pending) {
            return $out;
        }

        if (ApiGuard::isDown('jikan')) {
            foreach ($pending as $k => [, , $cached]) {
                $out[$k] = $this->staleData($cached);
            }

            return $out;
        }

        /** @var array $responses */
        $responses = Http::pool(function (Pool $pool) use ($pending) {
            foreach ($pending as $key => [$path]) {
                $pool->as($key)
                    ->acceptJson()
                    ->connectTimeout((int) config('otaku.api.connect_timeout'))
                    ->timeout((int) config('otaku.api.timeout'))
                    ->withUserAgent(config('otaku.jikan.user_agent'))
                    ->get($this->baseUrl.$path);
            }
        });

        foreach ($responses as $key => $response) {
            [$path, $key2, $cached] = $pending[$key];

            if ($response instanceof ConnectionException) {
                $this->lastError = 'Jikan: '.$response->getMessage();
                ApiGuard::markDown('jikan');
                $out[$key] = $this->staleData($cached);

                continue;
            }

            if (is_object($response) && method_exists($response, 'successful') && $response->successful()) {
                $data = $response->json();
                $out[$key] = is_array($data) ? $data : $this->staleData($cached);
                if (is_array($data)) {
                    $this->cachePut($key2, $data);
                }

                continue;
            }

            $this->lastError = 'Jikan HTTP '.(is_object($response) && method_exists($response, 'status') ? $response->status() : 'failed');
            $out[$key] = $this->staleData($cached);
        }

        return $out;
    }

    private function cached(string $prefixKey, callable $callback): array
    {
        $key = config('otaku.cache_prefix').':jikan:'.$prefixKey;

        $result = $callback();
        if ($result) {
            $this->cachePut($key, $result);

            return $result;
        }

        $cached = $this->cacheGet($key);

        return $this->staleData($cached);
    }
}
