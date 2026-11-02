<?php
$poster = fn($a) => image_url($a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? null);
$titleOf = fn($a) => $a['title'] ?? $a['title_english'] ?? 'Untitled';

// Pick 5 random items for slider
$allAvailable = array_merge($popular ?? [], $top ?? [], $manga ?? []);
shuffle($allAvailable);
$slides = array_slice($allAvailable, 0, 5);
?>

<section class="idlix-hero-slider">
  <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php foreach ($slides as $index => $s):
          $backdrop = $s['bannerImage'] ?? $s['images']['jpg']['large_image_url'] ?? null;
          $type = isset($s['chapters']) ? 'manga' : 'anime';
      ?>
        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>" style="background-image: url('<?= e($backdrop) ?>')">
          <div class="hero-overlay"></div>
          <div class="container idlix-hero-content">
            <h1 class="idlix-title"><?= e($titleOf($s)) ?></h1>
            <p class="idlix-desc"><?= e(safe_description($s['description'] ?? 'Temukan anime & manga favoritmu.')) ?></p>
            <div class="idlix-actions">
              <a class="btn btn-primary btn-lg px-4" href="<?= e(media_link($type, $s)) ?>"><i class="fa-solid fa-play me-2"></i> Watch Now</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
  </div>
</section>

<div class="container pb-5">
  <?php foreach (['Trending Anime' => $popular, 'Top Rated' => $top, 'Popular Manga' => $manga] as $title => $items): ?>
    <h3 class="section-title text-white"><?= e($title) ?></h3>
    <div class="net-scroller">
      <?php foreach (($items ?? []) as $a): ?>
        <?php $itemType = str_contains(strtolower($title), 'manga') ? 'manga' : 'anime'; ?>
        <a class="net-card" href="<?= e(media_link($itemType, $a)) ?>">
          <div class="net-poster-box">
            <img src="<?= e($poster($a)) ?>" alt="<?= e($titleOf($a)) ?>" loading="lazy">
            <span class="net-rating"><i class="fa-solid fa-star"></i> <?= e((string)($a['score'] ?? ($a['averageScore'] ? number_format($a['averageScore']/10, 1) : 'N/A'))) ?></span>
          </div>
          <div class="net-card-title"><?= e($titleOf($a)) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>
