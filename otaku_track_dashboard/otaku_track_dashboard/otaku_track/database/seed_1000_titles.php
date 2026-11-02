<?php
declare(strict_types=1);

/**
 * Otaku Track — Seeder / Importer 1.000 Judul Anime & Manga
 *
 * Mengambil 500 anime terpopuler + 500 manga terpopuler dari AniList GraphQL
 * dan menyimpannya langsung ke tabel `catalog_titles` di MySQL lokal.
 *
 * Dapat dijalankan melalui:
 * 1. CLI: php otaku_track/database/seed_1000_titles.php
 * 2. Browser: http://localhost/otaku_track_dashboard/otaku_track/database/seed_1000_titles.php
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';

$isCli = (PHP_SAPI === 'cli');

// Buat tabel jika belum ada
try {
    db()->exec("
        CREATE TABLE IF NOT EXISTS catalog_titles (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            external_id INT UNSIGNED NOT NULL,
            media_type ENUM('anime','manga') NOT NULL,
            title VARCHAR(255) NOT NULL,
            title_japanese VARCHAR(255) NULL,
            format VARCHAR(50) NULL,
            status VARCHAR(50) NULL,
            score DECIMAL(3,1) NULL,
            season_year INT NULL,
            episodes INT UNSIGNED NULL,
            chapters INT UNSIGNED NULL,
            genres VARCHAR(255) NULL,
            image_url VARCHAR(500) NULL,
            banner_url VARCHAR(500) NULL,
            synopsis TEXT NULL,
            popularity INT UNSIGNED DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_catalog_media (media_type, external_id),
            INDEX idx_media_type (media_type),
            INDEX idx_type_score (media_type, score),
            INDEX idx_type_popularity (media_type, popularity)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, "Error membuat tabel: " . $e->getMessage() . PHP_EOL);
        exit(1);
    } else {
        die("<div style='color:red;'>Gagal membuat tabel catalog_titles: " . htmlspecialchars($e->getMessage()) . "</div>");
    }
}

$currentCountAnime = count_local_catalog_titles('anime');
$currentCountManga = count_local_catalog_titles('manga');
$currentCountTotal = count_local_catalog_titles();

$action = $isCli ? 'run' : ($_GET['action'] ?? '');

if (!$isCli && $action !== 'run') {
    // Tampilan Web UI konfirmasi
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Sync 1.000 Judul — Otaku Track</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
      <style>
        body { background: #070e1a; color: #f5f7fb; font-family: system-ui, sans-serif; }
        .card { background: #111b2d; border: 1px solid #263651; border-radius: 12px; }
        .stat-box { background: #0b1322; border: 1px solid #24334d; border-radius: 8px; padding: 15px; }
      </style>
    </head>
    <body class="py-5">
      <div class="container" style="max-width: 680px;">
        <div class="card p-4 shadow-lg">
          <div class="d-flex align-items-center gap-3 mb-4">
            <div class="badge bg-primary fs-4 p-3"><i class="fa-solid fa-cloud-arrow-down"></i></div>
            <div>
              <h2 class="h4 mb-1">Sinkronisasi 1.000 Judul Anime & Manga</h2>
              <p class="text-secondary mb-0">Impor 500 anime + 500 manga populer langsung ke database lokal MySQL.</p>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-4">
              <div class="stat-box text-center">
                <small class="text-secondary d-block">Anime Tersimpan</small>
                <strong class="fs-4 text-info"><?= (int)$currentCountAnime ?></strong>
              </div>
            </div>
            <div class="col-4">
              <div class="stat-box text-center">
                <small class="text-secondary d-block">Manga Tersimpan</small>
                <strong class="fs-4 text-warning"><?= (int)$currentCountManga ?></strong>
              </div>
            </div>
            <div class="col-4">
              <div class="stat-box text-center">
                <small class="text-secondary d-block">Total di Database</small>
                <strong class="fs-4 text-success"><?= (int)$currentCountTotal ?></strong>
              </div>
            </div>
          </div>

          <div class="alert alert-dark border-secondary small mb-4">
            <i class="fa-solid fa-circle-info text-info me-2"></i>
            Proses ini akan mengirim 20 batch request ke AniList API dengan interval aman (0.5s per batch) untuk mengisi 1.000 judul lengkap dengan sinopsis, skor, tahun, dan link poster.
          </div>

          <div class="d-flex gap-2">
            <a class="btn btn-primary px-4 py-2" href="?action=run">
              <i class="fa-solid fa-play me-2"></i> Mulai Sinkronisasi Sekarang
            </a>
            <a class="btn btn-outline-secondary px-3" href="../index.php?page=home">
              Kembali ke Home
            </a>
          </div>
        </div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Eksekusi Seeder (CLI atau Browser saat ?action=run)
if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Sedang Menyinkronkan...</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo '<style>body{background:#070e1a;color:#f5f7fb;font-family:monospace;padding:24px;font-size:14px;}</style></head><body>';
    echo '<div style="max-width:800px;margin:auto;"><h3 style="color:#3f83f8;">Memulai Sinkronisasi 1.000 Judul...</h3><pre style="background:#111b2d;border:1px solid #263651;padding:16px;border-radius:8px;color:#cbd5e1;overflow-x:auto;">';
    ob_flush(); flush();
}

function logMsg(string $msg, bool $isCli): void
{
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo htmlspecialchars($msg) . "<br>";
        ob_flush(); flush();
    }
}

function fetchAniListBatch(string $type, int $page, int $perPage = 50): ?array
{
    $url = 'https://graphql.anilist.co';
    $query = <<<'GQL'
query ($type: MediaType, $page: Int, $perPage: Int) {
  Page(page: $page, perPage: $perPage) {
    pageInfo { hasNextPage total }
    media(type: $type, isAdult: false, sort: POPULARITY_DESC) {
      id
      idMal
      format
      status
      title { romaji english native }
      coverImage { large extraLarge }
      bannerImage
      description(asHtml: false)
      averageScore
      popularity
      seasonYear
      episodes
      chapters
      genres
    }
  }
}
GQL;
    $vars = [
        'type' => strtoupper($type),
        'page' => $page,
        'perPage' => $perPage,
    ];

    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['query' => $query, 'variables' => $vars]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: OtakuTrack-Seeder/1.0'],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ];
    if (defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE)) {
        $opts[CURLOPT_CAINFO] = API_CA_BUNDLE;
    }
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        return null;
    }
    $data = json_decode($res, true);
    return $data['data']['Page']['media'] ?? null;
}

$insertStmt = db()->prepare("
    INSERT INTO catalog_titles 
    (external_id, media_type, title, title_japanese, format, status, score, season_year, episodes, chapters, genres, image_url, banner_url, synopsis, popularity)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    score = VALUES(score),
    format = VALUES(format),
    status = VALUES(status),
    image_url = VALUES(image_url),
    banner_url = VALUES(banner_url),
    synopsis = VALUES(synopsis),
    popularity = VALUES(popularity)
");

$categories = [
    'anime' => 10, // 10 halaman x 50 = 500 anime
    'manga' => 10  // 10 halaman x 50 = 500 manga
];

$totalInserted = 0;

foreach ($categories as $kind => $totalPages) {
    logMsg("==================================================", $isCli);
    logMsg("Memproses {$totalPages} halaman untuk " . strtoupper($kind) . " (Target: " . ($totalPages * 50) . " judul)...", $isCli);
    logMsg("==================================================", $isCli);

    for ($p = 1; $p <= $totalPages; $p++) {
        logMsg("Mengambil {$kind} halaman {$p}/{$totalPages} (50 item)...", $isCli);
        $mediaList = fetchAniListBatch($kind, $p, 50);

        if (!$mediaList) {
            logMsg("  [WARNING] Gagal mengambil halaman {$p}. Mencoba ulang setelah jeda 2 detik...", $isCli);
            sleep(2);
            $mediaList = fetchAniListBatch($kind, $p, 50);
            if (!$mediaList) {
                logMsg("  [ERROR] Lewati halaman {$p} karena tidak ada respons.", $isCli);
                continue;
            }
        }

        $batchCount = 0;
        foreach ($mediaList as $item) {
            $extId = (int)($item['idMal'] ?: $item['id']);
            if ($extId < 1) continue;

            $title = $item['title']['english'] ?: ($item['title']['romaji'] ?: ($item['title']['native'] ?? 'Untitled'));
            $titleJp = $item['title']['native'] ?? null;
            $format = $item['format'] ?? null;
            $status = $item['status'] ?? null;
            $score = isset($item['averageScore']) ? round(((float)$item['averageScore']) / 10, 1) : null;
            $year = isset($item['seasonYear']) ? (int)$item['seasonYear'] : null;
            $episodes = isset($item['episodes']) ? (int)$item['episodes'] : null;
            $chapters = isset($item['chapters']) ? (int)$item['chapters'] : null;
            $genres = !empty($item['genres']) ? implode(', ', $item['genres']) : null;
            $image = $item['coverImage']['extraLarge'] ?? ($item['coverImage']['large'] ?? null);
            $banner = $item['bannerImage'] ?? null;
            $synopsis = $item['description'] ?? null;
            $pop = (int)($item['popularity'] ?? 0);

            try {
                $insertStmt->execute([
                    $extId,
                    $kind,
                    $title,
                    $titleJp,
                    $format,
                    $status,
                    $score,
                    $year,
                    $episodes,
                    $chapters,
                    $genres,
                    $image,
                    $banner,
                    $synopsis,
                    $pop
                ]);
                $batchCount++;
                $totalInserted++;
            } catch (Throwable $e) {
                // Ignore individual row error
            }
        }

        logMsg("  ✓ Berhasil menyimpan {$batchCount} judul {$kind}. (Total saat ini: {$totalInserted})", $isCli);

        // Jeda 0.4 detik agar tidak melanggar batas rate limit API
        usleep(400000);
    }
}

logMsg("==================================================", $isCli);
logMsg("SELESAI! Total judul yang diproses/diperbarui: {$totalInserted}", $isCli);
logMsg("Anime tersimpan: " . count_local_catalog_titles('anime'), $isCli);
logMsg("Manga tersimpan: " . count_local_catalog_titles('manga'), $isCli);
logMsg("Total judul di database lokal: " . count_local_catalog_titles(), $isCli);
logMsg("==================================================", $isCli);

if (!$isCli) {
    echo '</pre><a class="btn btn-success mt-3" href="../index.php?page=search"><i class="fa-solid fa-compass me-2"></i> Buka Halaman Explore Katalog</a> ';
    echo '<a class="btn btn-outline-light mt-3" href="../index.php?page=admin"><i class="fa-solid fa-chart-line me-2"></i> Buka Dashboard Admin</a></div></body></html>';
}
