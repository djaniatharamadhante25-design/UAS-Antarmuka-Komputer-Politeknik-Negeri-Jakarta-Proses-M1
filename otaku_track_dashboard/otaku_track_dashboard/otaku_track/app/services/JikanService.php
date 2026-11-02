<?php
declare(strict_types=1);
require_once __DIR__ . '/ApiGuard.php';
require_once __DIR__ . '/AniListService.php';

final class JikanService
{
    private AniListService $aniList;
    private string $baseUrl;
    private ?string $lastError = null;

    public function __construct(string $baseUrl = JIKAN_BASE_URL)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->aniList = new AniListService();
    }

    public function lastError(): ?string { return $this->lastError; }
    public function aniListService(): AniListService { return $this->aniList; }

    public function popularAnime(int $limit = 12): array
    {
        return $this->cached('home_popular_' . min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?' . http_build_query(['limit'=>min(12,$limit),'sfw'=>'true']));
            $data = $j['data'] ?? [];
            if ($data) return $data;
            return $this->aniList->popularAnime($limit);
        });
    }

    public function topAnime(int $limit = 6): array
    {
        return $this->cached('v8_home_top_score_' . min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?' . http_build_query(['filter'=>'bypopularity','limit'=>min(12,$limit),'sfw'=>'true']));
            $data = $j['data'] ?? [];
            if ($data) {
                usort($data, fn($a,$b)=>(float)($b['score']??0) <=> (float)($a['score']??0));
                return array_slice($data,0,min(12,$limit));
            }
            return $this->aniList->topAnime($limit);
        });
    }

    public function popularManga(int $limit = 12): array
    {
        return $this->cached('home_popular_manga_' . min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/manga?' . http_build_query(['filter'=>'bypopularity','limit'=>min(12,$limit)]));
            $data = $j['data'] ?? [];
            return $data ?: $this->aniList->popularManga($limit);
        });
    }

    public function browse(string $type, array $filters = [], int $page = 1, int $perPage = 28): array
    {
        // For the Explore page, AniList's browse query is the primary source and supports multi-filter browsing with pagination.
        $ani = $this->aniList->browse($type, $filters, $page, $perPage);
        if ($ani) return $ani;

        $endpoint = $type === 'manga' ? '/manga' : '/anime';
        $params = ['limit' => min(25, $perPage), 'page' => $page, 'sfw' => 'true'];
        foreach (['type','status','rating','min_score','order_by','sort','genres','start_date','end_date'] as $key) {
            if (($filters[$key] ?? '') !== '' && $filters[$key] !== null) $params[$key] = $filters[$key];
        }
        $j = $this->request($endpoint . '?' . http_build_query($params));
        return $j['data'] ?? [];
    }

    public function searchAnime(string $query, array $filters = [], int $page = 1, int $perPage = 28): array { return $this->search('/anime', $query, $filters, 'ANIME', $page, $perPage); }
    public function searchManga(string $query, array $filters = [], int $page = 1, int $perPage = 28): array { return $this->search('/manga', $query, $filters, 'MANGA', $page, $perPage); }

    public function anime(int $id): ?array
    {
        return $this->detailMerged('ANIME', $id);
    }

    public function manga(int $id): ?array
    {
        return $this->detailMerged('MANGA', $id);
    }

    /**
     * One resolver for every poster. Jikan/MAL supplies the broad MAL detail,
     * while AniList enriches the same title with tags, characters + VAs,
     * staff, studios, relations, external links, streaming and recommendations.
     * If either service is temporarily unavailable, the other remains usable.
     */
    private function detailMerged(string $type, int $malId): ?array
    {
        if ($malId < 1) return null;

        $kind = $type === 'MANGA' ? 'manga' : 'anime';
        $base = $this->request('/' . $kind . '/' . $malId . '/full')['data'] ?? null;
        if (!$base) {
            $base = $this->request('/' . $kind . '/' . $malId)['data'] ?? null;
        }
        if (!$base) return null;

        $base['mal_id'] = (int)($base['mal_id'] ?? $malId);
        $base['_source'] = 'Jikan / MyAnimeList';

        // Bagian tambahan diambil PARALEL (bukan berurutan). Kalau salah satu gagal,
        // detail utama tetap tampil.
        $extra = $this->requestMany([
            'characters'      => '/' . $kind . '/' . $malId . '/characters',
            'staff'           => '/' . $kind . '/' . $malId . '/staff',
            'recommendations' => '/' . $kind . '/' . $malId . '/recommendations',
        ]);

        $castPayload = $extra['characters']['data'] ?? [];
        if (is_array($castPayload) && array_key_exists('characters', $castPayload)) {
            $base['characters'] = $castPayload['characters'] ?? [];
            $base['staff'] = $castPayload['staff'] ?? [];
        } elseif ($castPayload) {
            $base['characters'] = $castPayload;
        }

        $staffResponse = $extra['staff']['data'] ?? [];
        if ($staffResponse) $base['staff'] = $staffResponse;

        $recommendations = $extra['recommendations']['data'] ?? [];
        if ($recommendations) $base['recommendations'] = $recommendations;

        return $base;
    }

    public function detailByAniListId(string $type,int $id): ?array
    {
        return $this->aniList->detailByAniListId($type,$id);
    }

    public function recommendations(int $id, string $type = 'anime'): array
    {
        if ($id < 1) return [];
        $type = $type === 'manga' ? 'manga' : 'anime';
        $aniType = strtoupper($type);
        $detail = $this->aniList->detailByMalId($aniType, $id);
        $ani = [];
        foreach (($detail['recommendations']['nodes'] ?? []) as $node) {
            $rec = $node['mediaRecommendation'] ?? null;
            if ($rec) $ani[] = $rec;
        }
        if ($ani) return $ani;

        $j = $this->request('/' . $type . '/' . $id . '/recommendations');
        return array_values(array_filter(array_map(function($item) {
            return $item['entry'] ?? $item;
        }, $j['data'] ?? [])));
    }

    private function search(string $endpoint, string $query, array $filters, string $aniType, int $page = 1, int $perPage = 28): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $params = ['q' => $query, 'page' => $page, 'limit' => min(25, $perPage), 'sfw' => 'true'];
        foreach(['type','status','rating','min_score','order_by','sort','genres','start_date','end_date'] as $key){
            if(($filters[$key]??'')!=='' && ($filters[$key]??null)!==null) $params[$key]=$filters[$key];
        }
        $json = $this->request($endpoint . '?' . http_build_query($params));
        $data = $json['data'] ?? [];
        if ($data) return $this->rank($data, $query);
        $fallback = $this->aniList->search($aniType, $query, $page, $perPage);
        return $this->rank($fallback, $query);
    }

    private function rank(array $items,string $query): array
    {
        $q=$this->normalize($query); $tokens=array_values(array_filter(explode(' ',$q)));
        foreach($items as &$item){
            $titles=[];
            foreach($item['titles']??[] as $t) if(!empty($t['title'])) $titles[]=$t['title'];
            foreach(['title','title_english','title_japanese'] as $key) if(!empty($item[$key])) $titles[]=$item[$key];
            $best=0;
            foreach(array_unique(array_map(fn($v)=>$this->normalize((string)$v),$titles)) as $title){
                if($title==='') continue;
                if($title===$q)$score=1000;
                elseif(str_starts_with($title,$q))$score=850;
                elseif(str_contains($title,$q))$score=700;
                else{ $matched=0; foreach($tokens as $token) if(str_contains($title,$token))$matched++; $score=$tokens?($matched/count($tokens))*500:0; }
                $best=max($best,$score);
            }
            $item['_relevance']=$best;
        }
        unset($item);
        usort($items,fn($a,$b)=>($b['_relevance']??0)<=>($a['_relevance']??0));
        return array_values(array_filter(array_slice($items,0,25),fn($a)=>(($a['_relevance']??0)>=200)));
    }

    private function normalize(string $s): string { return trim(preg_replace('/\s+/',' ',mb_strtolower($s))); }

    private static bool $jikanDown = false; // gagal sekali di request ini -> jangan coba lagi

    private function request(string $path): array
    {
        $this->lastError = null;
        $file = CACHE_DIR . '/' . sha1($path) . '.json';
        $fresh = is_file($file) && (time() - filemtime($file)) < API_CACHE_TTL;
        if ($fresh) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data)) return $data;
        }

        // Circuit breaker: kalau API baru saja timeout/gagal konek, jangan menunggu lagi.
        if (self::$jikanDown || ApiGuard::isDown('jikan')) {
            $this->lastError = 'Jikan sedang tidak terjangkau (dilewati sementara).';
            return $this->staleCache($file);
        }

        $ch = $this->buildHandle($path);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Retry hanya kalau masalahnya sertifikat SSL (bundle CA salah) - bukan timeout / HTTP error.
        if ($body === false && in_array($errno, [35, 58, 60, 77, 83], true)) {
            $ch = $this->buildHandle($path, false);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        return $this->handleResponse($body, $errno, $error, $status, $file);
    }

    private function buildHandle(string $path, bool $useBundle = true)
    {
        $ch = curl_init($this->baseUrl . $path);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: OtakuTrack/4.0'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_ENCODING => '',
        ];
        if ($useBundle && defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE)) $opts[CURLOPT_CAINFO] = API_CA_BUNDLE;
        curl_setopt_array($ch, $opts);
        return $ch;
    }

    private function handleResponse($body, int $errno, string $error, int $status, string $file): array
    {
        if ($body !== false && $status >= 200 && $status < 300) {
            $data = json_decode($body, true);
            if (is_array($data)) {
                @file_put_contents($file, $body);
                return $data;
            }
            $this->lastError = 'Jikan response bukan JSON valid.';
            return $this->staleCache($file);
        }

        $this->lastError = 'Jikan HTTP ' . $status . ($error ? ' — ' . $error : '');
        // 6 = DNS gagal, 7 = tidak bisa konek, 28 = timeout, 35/60 = SSL.
        if (in_array($errno, [6, 7, 28, 35, 52, 56, 60], true)) {
            self::$jikanDown = true;
            ApiGuard::markDown('jikan');
        }
        return $this->staleCache($file);
    }

    /** Data lama (kadaluarsa) lebih baik daripada halaman kosong / menunggu. */
    private function staleCache(string $file): array
    {
        if (is_file($file)) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data)) return $data;
        }
        return [];
    }

    /**
     * Ambil beberapa endpoint Jikan sekaligus (paralel) - bukan satu per satu.
     * @param array<string,string> $paths key => path
     * @return array<string,array>
     */
    private function requestMany(array $paths): array
    {
        $out = [];
        $pending = [];
        foreach ($paths as $key => $path) {
            $file = CACHE_DIR . '/' . sha1($path) . '.json';
            if (is_file($file) && (time() - filemtime($file)) < API_CACHE_TTL) {
                $data = json_decode((string)file_get_contents($file), true);
                if (is_array($data)) { $out[$key] = $data; continue; }
            }
            $pending[$key] = [$path, $file];
        }
        if (!$pending) return $out;

        if (self::$jikanDown || ApiGuard::isDown('jikan')) {
            foreach ($pending as $key => [$path, $file]) $out[$key] = $this->staleCache($file);
            return $out;
        }

        $mh = curl_multi_init();
        $handles = [];
        foreach ($pending as $key => [$path, $file]) {
            $handles[$key] = $this->buildHandle($path);
            curl_multi_add_handle($mh, $handles[$key]);
        }
        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) curl_multi_select($mh, 0.5);
        } while ($running && $status === CURLM_OK);

        foreach ($handles as $key => $ch) {
            [$path, $file] = $pending[$key];
            $body = curl_multi_getcontent($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            $out[$key] = $this->handleResponse($errno === 0 ? $body : false, $errno, $error, $code, $file);
        }
        curl_multi_close($mh);
        return $out;
    }

    private function cached(string $key, callable $callback): array
    {
        $file = CACHE_DIR . '/' . $key . '.json';
        $fresh = is_file($file) && (time() - filemtime($file)) < API_CACHE_TTL;
        if ($fresh) {
            $data = json_decode((string)file_get_contents($file), true);
            if (is_array($data)) return $data;
        }
        $data = $callback();
        if ($data) { @file_put_contents($file, json_encode($data)); return $data; }
        if (is_file($file)) { $old = json_decode((string)file_get_contents($file), true); if (is_array($old)) return $old; }
        return [];
    }
}
