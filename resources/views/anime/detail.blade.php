@extends('layouts.app')

@php
    $rawTitle = $media['title'] ?? 'Detail';
    $title = is_array($rawTitle)
        ? (string) ($rawTitle['english'] ?? $rawTitle['romaji'] ?? $rawTitle['native'] ?? $rawTitle['userPreferred'] ?? 'Detail')
        : (string) $rawTitle;

    $img = $media['coverImage']['extraLarge']
        ?? $media['coverImage']['large']
        ?? $media['images']['jpg']['large_image_url']
        ?? $media['images']['jpg']['image_url']
        ?? null;
    $banner = $media['bannerImage'] ?? null;
    $description = App\Support\OtakuHelper::safeDescription($media['description'] ?? $media['synopsis'] ?? null);

    $genreValues = [];
    foreach (($media['genres'] ?? []) as $genre) {
        $name = is_array($genre) ? ($genre['name'] ?? '') : (string) $genre;
        if ($name !== '') $genreValues[] = $name;
    }

    $tagValues = [];
    foreach (($media['tags'] ?? []) as $tag) {
        $name = is_array($tag) ? ($tag['name'] ?? '') : (string) $tag;
        if ($name !== '') $tagValues[] = $name;
    }
    $themeValues = [];
    foreach (($media['themes'] ?? []) as $theme) {
        $name = is_array($theme) ? ($theme['name'] ?? '') : (string) $theme;
        if ($name !== '') $themeValues[] = $name;
    }
    foreach (($media['demographics'] ?? []) as $demo) {
        $name = is_array($demo) ? ($demo['name'] ?? '') : (string) $demo;
        if ($name !== '') $themeValues[] = $name;
    }
    $themeTags = array_values(array_unique(array_merge($genreValues, $themeValues, $tagValues)));

    $studios = [];
    $studioRows = $media['studios']['nodes'] ?? $media['studios'] ?? [];
    if (is_array($studioRows)) {
        foreach ($studioRows as $studio) {
            $name = is_array($studio) ? ($studio['name'] ?? '') : (string) $studio;
            if ($name !== '') $studios[] = $name;
        }
    }

    $externalLinks = is_array($media['externalLinks'] ?? null) ? $media['externalLinks'] : [];
    $streaming = $media['streamingEpisodes'] ?? $media['streaming'] ?? [];
    if (! is_array($streaming)) $streaming = [];

    $score = null;
    if (isset($media['averageScore']) && $media['averageScore'] !== null) {
        $score = (float) $media['averageScore'] / 10;
    } elseif (isset($media['score']) && $media['score'] !== null && $media['score'] !== '') {
        $score = (float) $media['score'];
    }

    $format = (string) ($media['format'] ?? $media['type'] ?? 'Unknown');
    $status = (string) ($media['status'] ?? 'Unknown');
    $year = $media['seasonYear'] ?? ($media['aired']['prop']['from']['year'] ?? null);
    $source = (string) ($media['source'] ?? 'N/A');
    $origin = (string) ($media['countryOfOrigin'] ?? 'N/A');
    $season = $media['season'] ?? null;
    $startDate = $media['startDate'] ?? ($media['aired']['from'] ?? null);
    $endDate = $media['endDate'] ?? ($media['aired']['to'] ?? null);

    $detailDateLabel = function (mixed $date) {
        if (is_array($date)) {
            $y = $date['year'] ?? null;
            $m = $date['month'] ?? null;
            $d = $date['day'] ?? null;
            if ($y && $m && $d) return sprintf('%04d-%02d-%02d', $y, $m, $d);
            if ($y && $m) return sprintf('%04d-%02d', $y, $m);
            if ($y) return (string) $y;
            $from = $date['from'] ?? null;
            if (is_string($from)) return substr($from, 0, 10);
        }
        if (is_string($date) && $date !== '') return substr($date, 0, 10);

        return 'N/A';
    };

    $detailTypeLink = function (array $item, string $fallbackType) {
        $itemType = strtoupper((string) ($item['type'] ?? $fallbackType));

        return $itemType === 'MANGA' ? 'manga' : 'anime';
    };

    $myListArray = $myList ? [
        'status' => $myList->status,
        'score' => $myList->score,
        'progress' => $myList->progress,
    ] : null;
    $myListStatus = $myListArray['status'] ?? ($type === 'manga' ? 'plan_to_read' : 'plan_to_watch');
    $malId = (int) ($resolvedMalId ?? $media['mal_id'] ?? $media['idMal'] ?? 0);
    $anilistId = (int) ($resolvedAniId ?? $media['anilist_id'] ?? 0);
    $linkId = (int) $linkId ?: ($malId ?: $anilistId);
    $statuses = $type === 'manga' ? ['reading', 'completed', 'on_hold', 'dropped', 'plan_to_read'] : ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch'];
@endphp

@section('content')
<div class="detail-page">
  <section class="detail-hero" style="--cover:url('{{ e((string) ($banner ?: $img ?: '')) }}')">
    <div class="container py-5">
      <div class="row g-4 align-items-end">
        <div class="col-12 col-md-3 col-lg-2">
          <img class="detail-cover" src="{{ App\Support\OtakuHelper::imageUrl($img) }}" alt="{{ $title }}">
        </div>
        <div class="col-12 col-md-9 col-lg-10">
          <div class="eyebrow">MEDIA DETAIL</div>
          <h1 class="display-5 fw-bold mb-3">{{ $title }}</h1>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-warning">★ {{ $score !== null ? number_format($score, 1) : 'N/A' }}</span>
            <span class="badge badge-soft">{{ App\Support\OtakuHelper::formatStatus($status) }}</span>
            <span class="badge badge-soft">{{ $format }}</span>
            @if ($year)
                <span class="badge badge-soft">{{ $year }}</span>
            @endif
          </div>
          <div class="d-flex flex-wrap gap-2">
            @foreach (array_slice($genreValues, 0, 8) as $genre)
              <span class="chip">{{ $genre }}</span>
            @endforeach
          </div>
          <div class="detail-actions mt-4">
            @if (auth()->check())
              <button class="btn btn-primary" type="button" onclick="document.getElementById('my-list-card').scrollIntoView({behavior:'smooth'})"><i class="fa-solid fa-plus"></i> My List</button>
            @else
              <a class="btn btn-primary" href="{{ route('login') }}"><i class="fa-solid fa-right-to-bracket"></i> Login untuk My List</a>
            @endif
            <button class="btn btn-outline-light" type="button" data-bookmark data-id="{{ e((string) ($malId ?: $anilistId)) }}"><i class="fa-regular fa-bookmark"></i> <span>Bookmark</span></button>
            @if (! empty($media['siteUrl']))
              <a class="btn btn-outline-light" href="{{ (string) $media['siteUrl'] }}" target="_blank" rel="noopener">AniList <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="container py-4">
    <div class="row g-4">
      <div class="col-lg-8">

        <!-- 1. SYNOPSIS -->
        <section class="content-card mb-4" id="synopsis">
          <div class="card-eyebrow">STORY</div>
          <h3>Synopsis</h3>
          <p class="detail-synopsis mb-0">{!! nl2br(e($description)) !!}</p>
        </section>

        <!-- 2. INFORMATION -->
        <section class="content-card mb-4" id="information">
          <div class="card-eyebrow">INFORMATION</div>
          <h3>Information</h3>
          <div class="info-grid">
            <div><span>Score</span><strong>{{ $score !== null ? number_format($score, 1) . ' / 10' : 'N/A' }}</strong></div>
            <div><span>Popularity</span><strong>{{ isset($media['popularity']) ? (string) $media['popularity'] : 'N/A' }}</strong></div>
            <div><span>Rank</span><strong>{{ isset($media['rank']) ? '#' . (string) $media['rank'] : 'N/A' }}</strong></div>
            <div><span>Members / Favorites</span><strong>{{ isset($media['members']) ? (string) $media['members'] : (isset($media['favourites']) ? (string) $media['favourites'] : 'N/A') }}</strong></div>
            <div><span>Rating</span><strong>{{ (string) ($media['rating'] ?? $media['rating']['string'] ?? 'N/A') }}</strong></div>
            <div><span>{{ $type === 'manga' ? 'Chapters' : 'Episodes' }}</span><strong>{{ (string) ($type === 'manga' ? ($media['chapters'] ?? 'N/A') : ($media['episodes'] ?? 'N/A')) }}</strong></div>
            <div><span>{{ $type === 'manga' ? 'Volumes' : 'Duration' }}</span><strong>{{ (string) ($type === 'manga' ? ($media['volumes'] ?? 'N/A') : (($media['duration'] ?? 'N/A') !== 'N/A' ? $media['duration'] . ' min' : 'N/A')) }}</strong></div>
            <div><span>Format</span><strong>{{ $format }}</strong></div>
            <div><span>Year</span><strong>{{ $year ? (string) $year : 'N/A' }}</strong></div>
            <div><span>Start</span><strong>{{ $detailDateLabel($startDate) }}</strong></div>
            <div><span>End</span><strong>{{ $detailDateLabel($endDate) }}</strong></div>
            <div><span>Season</span><strong>{{ $season ? ucfirst(strtolower((string) $season)) : 'N/A' }}</strong></div>
            <div><span>Studio / Publisher</span><strong>{{ $studios ? implode(', ', $studios) : 'N/A' }}</strong></div>
            <div><span>Source</span><strong>{{ $source }}</strong></div>
            <div><span>Origin</span><strong>{{ $origin }}</strong></div>
          </div>
        </section>

        <!-- 3. THEMES & TAGS -->
        <section class="content-card mb-4" id="themes-tags">
          <div class="card-eyebrow">CLASSIFICATION</div>
          <h3>Themes &amp; Tags</h3>
          @if ($genreValues)
            <div class="detail-subtitle">Genres</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              @foreach ($genreValues as $genre)
                  <span class="chip">{{ $genre }}</span>
              @endforeach
            </div>
          @endif
          @if ($themeValues)
            <div class="detail-subtitle">Themes / Demographics</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              @foreach (array_slice($themeValues, 0, 30) as $tag)
                  <span class="chip">{{ $tag }}</span>
              @endforeach
            </div>
          @endif
          @if ($tagValues)
            <div class="detail-subtitle">AniList Tags</div>
            <div class="d-flex flex-wrap gap-2">
              @foreach (array_slice($tagValues, 0, 30) as $tag)
                  <span class="chip">{{ $tag }}</span>
              @endforeach
            </div>
          @elseif (! $themeValues && ! $genreValues)
            <div class="detail-empty"><i class="fa-solid fa-tags"></i><span>Belum ada data klasifikasi tambahan untuk judul ini.</span></div>
          @endif
        </section>

        <!-- 4. WHERE TO WATCH / READ -->
        <section class="content-card mb-4" id="where-to-watch">
          <div class="card-eyebrow">{{ $type === 'manga' ? 'READ' : 'WATCH' }}</div>
          <h3>{{ $type === 'manga' ? 'Where to Read / Official Links' : 'Where to Watch' }}</h3>
          @if ($watchLinks || $externalLinks || $streaming)
            <div class="platform-grid">
              @foreach ($watchLinks as $l)
                <a class="platform-card" href="{{ $l->url }}" target="_blank" rel="noopener">
                  <i class="fa-solid fa-circle-play"></i>
                  <div><strong>{{ $l->provider }}</strong><span>Official link from site admin</span></div>
                </a>
              @endforeach
              @foreach (array_slice($externalLinks, 0, 10) as $l)
                  @if (empty($l['url']))
                      @continue
                  @endif
                <a class="platform-card" href="{{ (string) $l['url'] }}" target="_blank" rel="noopener">
                  <i class="fa-solid fa-link"></i>
                  <div><strong>{{ (string) ($l['site'] ?? 'Official Site') }}</strong><span>{{ (string) ($l['type'] ?? 'External link') }}</span></div>
                </a>
              @endforeach
              @foreach (array_slice($streaming, 0, 10) as $s)
                  @if (empty($s['url']))
                      @continue
                  @endif
                <a class="platform-card" href="{{ (string) $s['url'] }}" target="_blank" rel="noopener">
                  <i class="fa-solid fa-tv"></i>
                  <div><strong>{{ (string) ($s['site'] ?? 'Streaming') }}</strong><span>{{ (string) ($s['title'] ?? 'Watch') }}</span></div>
                </a>
              @endforeach
            </div>
          @else
            <div class="detail-empty"><i class="fa-solid fa-tv"></i><span>Belum ada platform/link resmi yang dikembalikan API atau database admin.</span></div>
          @endif
        </section>

        <!-- OFFICIAL TRAILER -->
        @if (! empty($trailer['embed_url']))
          <section class="content-card mb-4" id="trailer">
            <div class="card-eyebrow">PREVIEW</div>
            <h3><i class="fa-brands fa-youtube text-danger me-2"></i> Official Trailer</h3>
            <div class="trailer-wrapper">
              <iframe
                src="{{ $trailer['embed_url'] }}"
                title="Official Trailer {{ $title }}"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy">
              </iframe>
            </div>
          </section>
        @endif

        <!-- 5. CHARACTERS & VOICE ACTORS -->
        <section class="content-card mb-4" id="characters">
          <div class="card-eyebrow">CAST</div>
          <h3>Characters &amp; Voice Actors</h3>
          @if ($characters)
            <div class="char-va-grid">
              @foreach (array_slice($characters, 0, 24) as $c)
                <div class="char-va-card">
                  <!-- Karakter (Kiri) -->
                  <div class="char-side">
                    <img class="char-avatar" src="{{ App\Support\OtakuHelper::imageUrl($c['image'] ?? null) }}" alt="{{ (string) $c['name'] }}" loading="lazy">
                    <div class="char-info">
                      <div class="char-name" title="{{ (string) $c['name'] }}">{{ (string) $c['name'] }}</div>
                      <div class="char-role">{{ (string) $c['role'] }}</div>
                    </div>
                  </div>

                  <!-- Voice Actor / Seiyuu (Kanan) -->
                  @if (! empty($c['voice']))
                    <div class="va-side">
                      <div class="va-info">
                        <div class="va-name" title="{{ (string) $c['voice'] }}">{{ (string) $c['voice'] }}</div>
                        <div class="va-lang">{{ (string) ($c['voice_language'] ?: 'Japanese') }}</div>
                      </div>
                      <img class="va-avatar" src="{{ App\Support\OtakuHelper::imageUrl($c['voice_image'] ?? null) }}" alt="{{ (string) $c['voice'] }}" loading="lazy">
                    </div>
                  @endif
                </div>
              @endforeach
            </div>
          @else
            <div class="detail-empty"><i class="fa-solid fa-users"></i><span>Data characters dan voice actors belum tersedia dari API untuk judul ini.</span></div>
          @endif
        </section>

        <!-- 6. STAFF & PRODUCTION -->
        <section class="content-card mb-4" id="staff">
          <div class="card-eyebrow">PRODUCTION</div>
          <h3>Staff &amp; Production</h3>
          @if ($studios)
            <div class="detail-subtitle mb-2">Studios / Animation &amp; Production</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              @foreach ($studios as $studio)
                  <span class="chip"><i class="fa-solid fa-building me-1"></i> {{ $studio }}</span>
              @endforeach
            </div>
          @endif
          @if ($staff)
            <div class="staff-grid">
              @foreach (array_slice($staff, 0, 18) as $person)
                <div class="staff-card">
                  <img class="staff-avatar" src="{{ App\Support\OtakuHelper::imageUrl($person['image'] ?? null) }}" alt="{{ (string) $person['name'] }}" loading="lazy">
                  <div class="staff-info">
                    <strong class="staff-name">{{ (string) $person['name'] }}</strong>
                    <span class="staff-role">{{ (string) $person['role'] }}</span>
                  </div>
                </div>
              @endforeach
            </div>
          @elseif (! $studios)
            <div class="detail-empty"><i class="fa-solid fa-user-gear"></i><span>Data staff dan production belum tersedia dari API untuk judul ini.</span></div>
          @endif
        </section>

        <!-- 7. RELATIONS -->
        <section class="content-card mb-4" id="relations">
          <div class="card-eyebrow">FRANCHISE</div>
          <h3>Relations</h3>
          @if ($relations)
            <div class="relation-list">
              @foreach (array_slice($relations, 0, 18) as $relation)
                @php
                    $relationType = $detailTypeLink(['type' => $relation['type'] ?? $type], $type);
                    $relationItem = [
                        'mal_id' => (int) ($relation['mal_id'] ?? 0),
                        'anilist_id' => (int) ($relation['anilist_id'] ?? 0),
                    ];
                @endphp
                <a class="relation-card" href="{{ App\Support\OtakuHelper::mediaLink($relationType, $relationItem) }}">
                  <span>{{ str_replace('_', ' ', (string) $relation['relation']) }}</span>
                  <strong>{{ (string) $relation['title'] }}</strong>
                </a>
              @endforeach
            </div>
          @else
            <div class="detail-empty"><i class="fa-solid fa-diagram-project"></i><span>Belum ada relation (prequel, sequel, side story, alternative, dll.) dari API.</span></div>
          @endif
        </section>

        <!-- 8. RECOMMENDATIONS -->
        <section class="content-card mb-4" id="recommendations">
          <div class="card-eyebrow">DISCOVER</div>
          <h3>Recommendations</h3>
          @if ($recommendations)
            <div class="media-grid mini-grid">
              @foreach (array_slice($recommendations, 0, 8) as $recommendation)
                @php
                    $recommendationType = $detailTypeLink($recommendation, $type);
                    $recommendationItem = [
                        'mal_id' => (int) ($recommendation['mal_id'] ?? 0),
                        'anilist_id' => (int) ($recommendation['anilist_id'] ?? 0),
                    ];
                    $recScore = $recommendation['score'] ?? null;
                @endphp
                <a class="media-card" href="{{ App\Support\OtakuHelper::mediaLink($recommendationType, $recommendationItem) }}">
                  <div class="poster">
                    <img src="{{ App\Support\OtakuHelper::imageUrl($recommendation['image'] ?? null) }}" alt="{{ (string) $recommendation['title'] }}" loading="lazy">
                    @if ($recScore !== null)
                        <span class="score"><i class="fa-solid fa-star"></i> {{ number_format((float) $recScore, 1) }}</span>
                    @endif
                  </div>
                  <div class="media-title">{{ (string) $recommendation['title'] }}</div>
                </a>
              @endforeach
            </div>
          @else
            <div class="detail-empty"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Belum ada recommendations yang dikembalikan API untuk judul ini.</span></div>
          @endif
        </section>

      </div>

      <aside class="col-lg-4">
        @if (auth()->check())
          <div class="content-card sticky-lg-top" id="my-list-card" style="top:90px">
            <div class="card-eyebrow">PERSONAL TRACKER</div>
            <h3>My List</h3>
            <p class="text-secondary small">Simpan status, skor pribadi, dan progress seperti tracker ala AniList.</p>
            <form method="post" action="{{ route('media_list.save') }}">
              @csrf
              <input type="hidden" name="media_type" value="{{ $type }}">
              <input type="hidden" name="external_id" value="{{ (string) $linkId }}">
              <input type="hidden" name="mal_id" value="{{ (string) $malId }}">
              <input type="hidden" name="anilist_id" value="{{ (string) $anilistId }}">
              <input type="hidden" name="title" value="{{ $title }}">
              <input type="hidden" name="image_url" value="{{ (string) $img }}">
              <label class="form-label">Status</label>
              <select name="status" class="form-select mb-3">
                @foreach ($statuses as $itemStatus)
                  <option value="{{ $itemStatus }}" {{ $myListStatus === $itemStatus ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $itemStatus)) }}</option>
                @endforeach
              </select>
              <label class="form-label">My Score</label>
              <input name="score" type="number" min="0" max="10" step=".1" class="form-control mb-3" value="{{ (string) ($myListArray['score'] ?? '') }}">
              <label class="form-label">Progress</label>
              <input name="progress" type="number" min="0" class="form-control mb-3" value="{{ (string) ($myListArray['progress'] ?? 0) }}">
              <button class="btn btn-primary w-100"><i class="fa-solid fa-bookmark"></i> Simpan ke My List</button>
            </form>
          </div>
        @else
          <div class="content-card" id="my-list-card">
            <div class="card-eyebrow">PERSONAL TRACKER</div>
            <h3>My List</h3>
            <p class="text-secondary">Login untuk menyimpan judul ini ke watchlist/reading list dan memberi score sendiri.</p>
            <a class="btn btn-primary w-100" href="{{ route('login') }}">Login</a>
          </div>
        @endif

        <div class="content-card mt-3">
          <div class="card-eyebrow">SOURCE</div>
          <h3>Data</h3>
          <div class="source-list">
            <div><span>MAL ID</span><strong>{{ $malId ? (string) $malId : 'N/A' }}</strong></div>
            <div><span>AniList ID</span><strong>{{ $anilistId ? (string) $anilistId : 'N/A' }}</strong></div>
            <div><span>Resolver</span><strong>{{ (string) ($media['_source'] ?? 'API') }}</strong></div>
            <div><span>Site</span><strong>{{ ! empty($media['siteUrl']) ? 'AniList' : 'Jikan / MAL' }}</strong></div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>
@endsection
