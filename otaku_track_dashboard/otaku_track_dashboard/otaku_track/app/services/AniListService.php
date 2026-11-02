<?php
declare(strict_types=1);

require_once __DIR__ . '/ApiGuard.php';

final class AniListService
{
    private string $url = 'https://graphql.anilist.co';
    private ?string $lastError = null;
    private static bool $down = false;

    public function lastError(): ?string { return $this->lastError; }

    public function popularAnime(int $limit = 12): array { return $this->list('ANIME', 'POPULARITY_DESC', $limit); }
    public function topAnime(int $limit = 6): array { return $this->list('ANIME', 'SCORE_DESC', $limit); }
    public function popularManga(int $limit = 12): array { return $this->list('MANGA', 'POPULARITY_DESC', $limit); }

    public function browse(string $type, array $filters = [], int $page = 1, int $perPage = 28): array
    {
        if ($page > 50 && $perPage === 28) {
            $perPage = min(50, $page);
            $page = 1;
        }
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $variables = [
            'type' => $type,
            'page' => $page,
            'perPage' => $perPage,
            'isAdult' => false,
            'sort' => [$this->mapSort((string)($filters['sort'] ?? 'POPULARITY_DESC'))],
        ];
        if (($filters['format'] ?? '') !== '') $variables['format'] = [strtoupper((string)$filters['format'])];
        if (($filters['status'] ?? '') !== '') $variables['status'] = strtoupper((string)$filters['status']);
        if (($filters['season'] ?? '') !== '') $variables['season'] = strtoupper((string)$filters['season']);
        if (($filters['year'] ?? '') !== '') $variables['seasonYear'] = (int)$filters['year'];
        if (($filters['genre'] ?? '') !== '') $variables['genre'] = [(string)$filters['genre']];

        $query = <<<'GQL'
query ($type: MediaType, $page: Int, $perPage: Int, $isAdult: Boolean, $format: [MediaFormat], $status: MediaStatus, $season: MediaSeason, $seasonYear: Int, $genre: [String], $sort: [MediaSort]) {
  Page(page: $page, perPage: $perPage) {
    pageInfo { total currentPage lastPage hasNextPage }
    media(type: $type, isAdult: $isAdult, format_in: $format, status: $status, season: $season, seasonYear: $seasonYear, genre_in: $genre, sort: $sort) {
      id idMal type format status title { romaji english native }
      coverImage { large extraLarge color }
      bannerImage description(asHtml:false)
      averageScore popularity favourites seasonYear episodes chapters volumes genres
    }
  }
}
GQL;

        $json = $this->request($query, $variables);
        if (!$json) return [];

        $items = [];
        foreach (($json['data']['Page']['media'] ?? []) as $m) {
            $items[] = $this->normalizeMedia($m, $type);
        }
        return $items;
    }

    public function search(string $type, string $query, int $page = 1, int $limit = 28): array
    {
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $query = trim($query);
        if ($query === '') return [];
        $page = max(1, $page);
        $limit = max(1, min(50, $limit));
        $q = <<<'GQL'
query ($type: MediaType, $search: String, $page: Int, $perPage: Int) {
  Page(page: $page, perPage: $perPage) {
    pageInfo { total currentPage lastPage hasNextPage }
    media(type: $type, search: $search, isAdult: false, sort: SEARCH_MATCH) {
      id idMal type format status title { romaji english native }
      coverImage { large extraLarge color }
      bannerImage description(asHtml:false)
      averageScore popularity seasonYear episodes chapters volumes genres
    }
  }
}
GQL;
        $json = $this->request($q, ['type'=>$type,'search'=>$query,'page'=>$page,'perPage'=>$limit]);
        return $this->normalizeList($json['data']['Page']['media'] ?? [], $type);
    }

    public function detailByMalId(string $type, int $malId): ?array
    {
        if ($malId < 1) return null;
        return $this->detail($type, ['idMal' => $malId]);
    }

    public function detailByAniListId(string $type, int $id): ?array
    {
        if ($id < 1) return null;
        return $this->detail($type, ['id' => $id]);
    }

    private function detail(string $type, array $where): ?array
    {
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $vars = ['type' => $type];
        if (!empty($where['id'])) {
            $argDef = '$type: MediaType, $id: Int';
            $mediaArg = 'type: $type, id: $id';
            $vars['id'] = (int)$where['id'];
        } elseif (!empty($where['idMal'])) {
            $argDef = '$type: MediaType, $idMal: Int';
            $mediaArg = 'type: $type, idMal: $idMal';
            $vars['idMal'] = (int)$where['idMal'];
        } else {
            return null;
        }

        $fields = <<<'FIELDS'
    id idMal type format status
    title { romaji english native userPreferred }
    synonyms
    description(asHtml:false)
    startDate { year month day }
    endDate { year month day }
    season seasonYear
    episodes duration chapters volumes
    countryOfOrigin source
    averageScore meanScore popularity trending favourites
    genres tags { id name rank }
    coverImage { large extraLarge color }
    bannerImage
    siteUrl
    trailer { id site thumbnail }
    studios(isMain:true) { nodes { id name isAnimationStudio siteUrl } }
    externalLinks { id url site siteId type language icon }
    streamingEpisodes { title thumbnail url site }
    characters(sort:[ROLE, FAVOURITES_DESC], page:1, perPage:24) {
      edges {
        role
        voiceActors { id name { full native } image { large medium } siteUrl }
        node { id name { full native } image { large medium } siteUrl }
      }
    }
    staff(sort:[RELEVANCE, FAVOURITES_DESC], page:1, perPage:18) {
      edges { role node { id name { full native } image { large } siteUrl } }
    }
    relations {
      edges { relationType node { id idMal type format title { romaji english native } coverImage { medium large } averageScore } }
    }
    recommendations(sort:RATING_DESC, page:1, perPage:12) {
      nodes { id rating mediaRecommendation { id idMal type title { romaji english native } coverImage { medium large } averageScore } }
    }
FIELDS;

        $q = "query ({$argDef}) { Media({$mediaArg}) { {$fields} } }";
        $json = $this->request($q, $vars);
        $m = $json['data']['Media'] ?? null;
        return $m ?: null;
    }

    private function list(string $type, string $sort, int $limit): array
    {
        return $this->browse($type, ['sort'=>$sort], $limit);
    }

    private function normalizeList(array $items, string $type): array
    {
        $out = [];
        foreach ($items as $m) $out[] = $this->normalizeMedia($m, $type);
        return $out;
    }

    private function normalizeMedia(array $m, string $type): array
    {
        $title = $m['title']['english'] ?: ($m['title']['romaji'] ?: ($m['title']['native'] ?? 'Untitled'));
        return [
            'mal_id' => (int)($m['idMal'] ?? 0),
            'anilist_id' => (int)($m['id'] ?? 0),
            'title' => $title,
            'title_english' => $m['title']['english'] ?? null,
            'title_japanese' => $m['title']['native'] ?? null,
            'type' => $m['format'] ?? $type,
            'format' => $m['format'] ?? null,
            'score' => isset($m['averageScore']) ? ((float)$m['averageScore'] / 10) : null,
            'averageScore' => $m['averageScore'] ?? null,
            'popularity' => $m['popularity'] ?? null,
            'year' => $m['seasonYear'] ?? null,
            'images' => ['jpg' => [
                'image_url' => $m['coverImage']['large'] ?? null,
                'large_image_url' => $m['coverImage']['extraLarge'] ?? ($m['coverImage']['large'] ?? null),
            ]],
            'coverImage' => $m['coverImage'] ?? [],
            'bannerImage' => $m['bannerImage'] ?? null,
            'description' => $m['description'] ?? null,
            'status' => $m['status'] ?? null,
            'genres' => array_map(fn($g)=>['name'=>$g], $m['genres'] ?? []),
            'episodes' => $m['episodes'] ?? null,
            'chapters' => $m['chapters'] ?? null,
            'volumes' => $m['volumes'] ?? null,
            'siteUrl' => $m['siteUrl'] ?? null,
            '_source' => 'AniList',
        ];
    }

    private function mapSort(string $sort): string
    {
        $allowed = ['POPULARITY_DESC','SCORE_DESC','TRENDING_DESC','FAVOURITES_DESC','START_DATE_DESC','UPDATED_AT_DESC'];
        return in_array($sort, $allowed, true) ? $sort : 'POPULARITY_DESC';
    }

    private function request(string $query, array $variables): array
    {
        $this->lastError = null;
        $cacheFile = CACHE_DIR . '/anilist_' . sha1($query . '|' . json_encode($variables)) . '.json';
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < API_CACHE_TTL) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }
        if (self::$down || ApiGuard::isDown('anilist')) {
            $this->lastError = 'AniList sedang tidak terjangkau (dilewati sementara).';
            return $this->staleCache($cacheFile);
        }
        $ch = curl_init($this->url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query'=>$query,'variables'=>$variables]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','Accept: application/json','User-Agent: OtakuTrack/3.0'],
            CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
            CURLOPT_TIMEOUT => API_TIMEOUT + 4,
            CURLOPT_ENCODING => '',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];
        if (defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE)) $opts[CURLOPT_CAINFO] = API_CA_BUNDLE;
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) {
            $this->lastError = 'AniList cURL: '.($error ?: 'request gagal');
            if (in_array($errno, [6, 7, 28, 35, 52, 56, 60], true)) { self::$down = true; ApiGuard::markDown('anilist'); }
            return $this->staleCache($cacheFile);
        }
        if ($status < 200 || $status >= 300) { $this->lastError = 'AniList HTTP '.$status; return $this->staleCache($cacheFile); }
        $data = json_decode($body, true);
        if (!is_array($data)) { $this->lastError = 'Respons AniList bukan JSON valid.'; return []; }
        if (!empty($data['errors'])) { $this->lastError = 'AniList GraphQL: '.($data['errors'][0]['message'] ?? 'error'); return []; }
        @file_put_contents($cacheFile, json_encode($data));
        return $data;
    }

    private function staleCache(string $file): array
    {
        if (is_file($file)) {
            $d = json_decode((string)file_get_contents($file), true);
            if (is_array($d)) return $d;
        }
        return [];
    }
}
