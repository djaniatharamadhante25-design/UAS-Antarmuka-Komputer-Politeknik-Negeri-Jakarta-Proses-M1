@extends('layouts.app')

@php
    $typeName = $type === 'manga' ? 'Manga' : 'Anime';
    $poster = fn ($a) => App\Support\OtakuHelper::imageUrl($a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? null);
    $titleOf = fn ($a) => $a['title'] ?? $a['title_english'] ?? 'Untitled';
    $genres = ['Action', 'Adventure', 'Comedy', 'Drama', 'Fantasy', 'Horror', 'Mystery', 'Romance', 'Sci-Fi', 'Sports', 'Supernatural', 'Thriller'];
    $formats = $type === 'manga' ? ['MANGA', 'NOVEL', 'ONE_SHOT', 'MANHWA', 'MANHUA'] : ['TV', 'MOVIE', 'ONA', 'OVA', 'SPECIAL', 'MUSIC'];
    $currentPage = max(1, (int) $p);

    $buildPageUrl = function (int $target) use ($type, $q, $filters): string {
        $params = ['type' => $type, 'p' => $target];
        if ($q !== '') $params['q'] = $q;
        foreach ($filters as $k => $v) {
            if ($v !== null && $v !== '') $params[$k] = $v;
        }
        return route('search') . '?' . http_build_query($params);
    };
@endphp

@section('content')
<div class="container py-4" data-search-page data-q="{{ $q }}" data-type="{{ $type }}" data-page="{{ $currentPage }}">
  <div class="section-head mb-4">
    <div>
      <div class="eyebrow">EXPLORE KATALOG</div>
      <h1>{{ $typeName }}</h1>
      <p class="text-secondary mb-0">{{ $q !== '' ? 'Hasil pencarian untuk “' . $q . '”.' : 'Jelajahi 1.000+ judul anime & manga dengan filter lengkap dan pagination.' }}</p>
    </div>
    <span class="catalog-count"><strong id="search-result-count">{{ count($results ?? []) }}</strong> judul (Hal. {{ $currentPage }})</span>
  </div>

  <div class="explore-layout">
    <aside class="filter-sidebar">
      <form method="get" action="{{ route('search') }}">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="filter-title"><span>Filter</span><a href="{{ route('search', ['type' => $type]) }}">Reset</a></div>
        <label>Search</label><input class="form-control" name="q" value="{{ $q }}" placeholder="Cari judul..."><br>
        <label>Format</label><select class="form-select" name="format"><option value="">Semua format</option>@foreach ($formats as $f)<option value="{{ $f }}" {{ ($filters['type'] ?? '') === $f ? 'selected' : '' }}>{{ $f }}</option>@endforeach</select><br>
        <label>Genre</label><select class="form-select" name="genre"><option value="">Semua genre</option>@foreach ($genres as $g)<option value="{{ $g }}" {{ request()->query('genre') === $g ? 'selected' : '' }}>{{ $g }}</option>@endforeach</select><br>
        <label>Season</label><select class="form-select" name="season"><option value="">Semua season</option>@foreach (['WINTER', 'SPRING', 'SUMMER', 'FALL'] as $s)<option value="{{ $s }}" {{ request()->query('season') === $s ? 'selected' : '' }}>{{ $s }}</option>@endforeach</select><br>
        <label>Year</label><input class="form-control" name="year" type="number" min="1960" max="2100" value="{{ (string) request()->query('year', '') }}" placeholder="2026"><br>
        <label>Min. Score</label><input class="form-control" name="min_score" type="number" min="0" max="10" step="0.1" value="{{ (string) request()->query('min_score', '') }}" placeholder="7.0"><br>
        <label>Sort</label><select class="form-select" name="sort"><option value="desc" {{ request()->query('sort', 'desc') === 'desc' ? 'selected' : '' }}>Terbaru / tinggi</option><option value="asc" {{ request()->query('sort') === 'asc' ? 'selected' : '' }}>Terendah</option></select>
        <button class="filter-btn" type="submit">Terapkan filter</button>
      </form>
    </aside>

    <section>
      <div class="results-bar">
        <span><strong id="catalog-label">{{ count($results ?? []) }}</strong> judul ditampilkan di Halaman {{ $currentPage }}</span>
        <span class="muted">Katalog 1.000+ Judul (AniList + Jikan + Database)</span>
      </div>

      <div class="media-grid search-results-grid" id="search-results-grid">
        @foreach (($results ?? []) as $a)
            <a class="media-card" href="{{ App\Support\OtakuHelper::mediaLink($type, $a) }}"><div class="poster"><img src="{{ $poster($a) }}" alt="{{ $titleOf($a) }}" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> {{ $a['score'] ?? 'N/A' }}</span></div><div class="media-title">{{ $titleOf($a) }}</div><div class="media-meta">{{ $a['type'] ?? $typeName }} · {{ $a['year'] ?? '' }}</div></a>
        @endforeach
      </div>

      @if (empty($results))
        <div class="empty-grid-message" id="search-loading">Memuat katalog...</div>
      @else
        <!-- PAGINATION BAR -->
        <nav class="pagination-bar" aria-label="Navigasi Halaman Katalog">
          @if ($currentPage > 1)
            <a class="page-btn" href="{{ $buildPageUrl(1) }}" title="Halaman Pertama"><i class="fa-solid fa-angles-left"></i></a>
            <a class="page-btn" href="{{ $buildPageUrl($currentPage - 1) }}"><i class="fa-solid fa-angle-left"></i> Prev</a>
          @else
            <span class="page-btn disabled"><i class="fa-solid fa-angle-left"></i> Prev</span>
          @endif

          @php
              $startPage = max(1, $currentPage - 2);
              $endPage = max($startPage + 4, $currentPage + 2);
              $maxEstPage = 40; // 40 x 28 = ~1.120 judul
              if ($endPage > $maxEstPage) {
                  $endPage = $maxEstPage;
                  $startPage = max(1, $endPage - 4);
              }
          @endphp
          @for ($i = $startPage; $i <= $endPage; $i++)
            <a class="page-btn {{ $i === $currentPage ? 'active' : '' }}" href="{{ $buildPageUrl($i) }}">{{ $i }}</a>
          @endfor

          @if (count($results ?? []) >= 10)
            <a class="page-btn" href="{{ $buildPageUrl($currentPage + 1) }}">Next <i class="fa-solid fa-angle-right"></i></a>
            <a class="page-btn" href="{{ $buildPageUrl($maxEstPage) }}" title="Halaman {{ $maxEstPage }}"><i class="fa-solid fa-angles-right"></i></a>
          @else
            <span class="page-btn disabled">Next <i class="fa-solid fa-angle-right"></i></span>
          @endif
        </nav>
      @endif
    </section>
  </div>
</div>
@endsection
