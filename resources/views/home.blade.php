@extends('layouts.app')

@php
    $poster = fn ($a) => App\Support\OtakuHelper::imageUrl($a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? null);
    $titleOf = fn ($a) => $a['title'] ?? $a['title_english'] ?? 'Untitled';

    // Ambil 5 judul acak untuk slider
    $allAvailable = array_merge($popular ?? [], $top ?? [], $manga ?? []);
    shuffle($allAvailable);
    $slides = array_slice($allAvailable, 0, 5);
@endphp

@section('content')
<section class="idlix-hero-slider">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
      @foreach ($slides as $index => $s)
          @php
              $backdrop = $s['bannerImage'] ?? $s['images']['jpg']['large_image_url'] ?? null;
              $slideType = isset($s['chapters']) ? 'manga' : 'anime';
          @endphp
        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}" style="background-image: url('{{ $backdrop }}')">
          <div class="hero-overlay"></div>
          <div class="container idlix-hero-content">
            <h1 class="idlix-title">{{ $titleOf($s) }}</h1>
            <p class="idlix-desc">{{ App\Support\OtakuHelper::safeDescription($s['description'] ?? 'Temukan anime & manga favoritmu.') }}</p>
            <div class="idlix-actions">
              <a class="btn btn-primary btn-lg px-4" href="{{ App\Support\OtakuHelper::mediaLink($slideType, $s) }}"><i class="fa-solid fa-play me-2"></i> Watch Now</a>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
  </div>
</section>

<div class="container pb-5">
  @foreach (['Trending Anime' => $popular, 'Top Rated' => $top, 'Popular Manga' => $manga] as $title => $items)
    <h3 class="section-title text-white">{{ $title }}</h3>
    <div class="net-scroller">
      @foreach (($items ?? []) as $a)
          @php $itemType = str_contains(strtolower($title), 'manga') ? 'manga' : 'anime'; @endphp
        <a class="net-card" href="{{ App\Support\OtakuHelper::mediaLink($itemType, $a) }}">
          <div class="net-poster-box">
            <img src="{{ $poster($a) }}" alt="{{ $titleOf($a) }}" loading="lazy">
            <span class="net-rating"><i class="fa-solid fa-star"></i> {{ $a['score'] ?? ($a['averageScore'] ? number_format($a['averageScore'] / 10, 1) : 'N/A') }}</span>
          </div>
          <div class="net-card-title">{{ $titleOf($a) }}</div>
        </a>
      @endforeach
    </div>
  @endforeach
</div>
@endsection
