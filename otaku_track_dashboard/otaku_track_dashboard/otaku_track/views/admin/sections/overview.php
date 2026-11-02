<?php
/** @var array $counts @var array $chart_days @var array $chart_users @var array $chart_lists @var array $statuses @var array $top @var array $activity */
$stats = [
    ['Pengguna',      $counts['users'],   '+' . $counts['users_week'] . ' minggu ini · ' . $counts['admins'] . ' admin',        $counts['users_week'],   'fa-users'],
    ['Entri My List', $counts['lists'],   '+' . $counts['lists_week'] . ' minggu ini',                                            $counts['lists_week'],   'fa-bookmark'],
    ['Thread forum',  $counts['threads'], '+' . $counts['threads_week'] . ' minggu ini · ' . $counts['replies'] . ' balasan',    $counts['threads_week'], 'fa-comments'],
    ['Link tonton/baca', $counts['links'], $counts['categories'] . ' kategori forum',                                              0,                       'fa-link'],
];
$statusMax = $statuses ? max(array_column($statuses, 'total')) : 1;
?>
<div class="adm-stats">
  <?php foreach ($stats as [$label, $value, $sub, $delta, $icon]): ?>
    <div class="adm-stat">
      <div class="adm-stat-top"><span><?= e($label) ?></span><i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i></div>
      <strong><?= (int) $value ?></strong>
      <small class="<?= $delta > 0 ? 'is-up' : '' ?>"><?= e($sub) ?></small>
    </div>
  <?php endforeach; ?>
</div>

<div class="adm-grid adm-grid-wide">
  <div class="adm-card">
    <div class="adm-card-head">
      <h2>Aktivitas 14 hari terakhir</h2>
      <div class="adm-legend"><span class="lg-a">Pengguna baru</span><span class="lg-b">Entri My List baru</span></div>
    </div>
    <?php if (array_sum($chart_users) + array_sum($chart_lists) === 0): ?>
      <p class="adm-empty">Belum ada pendaftaran atau entri list dalam 14 hari terakhir.</p>
    <?php else: ?>
      <?= admin_chart($chart_days, $chart_users, $chart_lists, 'pengguna baru', 'entri list') ?>
    <?php endif; ?>
  </div>

  <div class="adm-card">
    <div class="adm-card-head"><h2>Perlu dicek</h2></div>
    <ul class="adm-todo">
      <li>
        <a href="<?= e(admin_url(['section' => 'forum', 'filter' => 'hidden'])) ?>">
          <i class="fa-solid fa-eye-slash" aria-hidden="true"></i><span>Thread disembunyikan</span><b><?= (int) $counts['threads_hidden'] ?></b>
        </a>
      </li>
      <li>
        <a href="<?= e(admin_url(['section' => 'forum', 'filter' => 'locked'])) ?>">
          <i class="fa-solid fa-lock" aria-hidden="true"></i><span>Thread dikunci</span><b><?= (int) $counts['threads_locked'] ?></b>
        </a>
      </li>
      <li>
        <a href="<?= e(admin_url(['section' => 'forum', 'tab' => 'replies', 'filter' => 'hidden'])) ?>">
          <i class="fa-solid fa-reply" aria-hidden="true"></i><span>Balasan disembunyikan</span><b><?= (int) $counts['replies_hidden'] ?></b>
        </a>
      </li>
      <li>
        <a href="<?= e(admin_url(['section' => 'users', 'role' => 'admin'])) ?>">
          <i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Akun admin aktif</span><b><?= (int) $counts['admins'] ?></b>
        </a>
      </li>
      <li>
        <a href="<?= e(asset_url('database/seed_1000_titles.php')) ?>" target="_blank" title="Buka Seeder 1.000 Judul">
          <i class="fa-solid fa-cloud-arrow-down text-info" aria-hidden="true"></i><span>Katalog Database Lokal</span><b><?= (int) ($counts['catalog_titles'] ?? 0) ?> judul</b>
        </a>
      </li>
    </ul>
  </div>
</div>

<div class="adm-grid">
  <div class="adm-card">
    <div class="adm-card-head"><h2>Status di My List</h2><small><?= (int) $counts['lists'] ?> entri</small></div>
    <?php if (!$statuses): ?>
      <p class="adm-empty">Belum ada pengguna yang menyimpan anime atau manga ke My List.</p>
    <?php else: ?>
      <ul class="adm-bars">
        <?php foreach ($statuses as $row): ?>
          <li>
            <span><?= e(admin_status_label((string) $row['status'])) ?></span>
            <div class="adm-bar" role="presentation"><i style="width:<?= max(3, round($row['total'] / $statusMax * 100)) ?>%"></i></div>
            <b><?= (int) $row['total'] ?></b>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>

  <div class="adm-card">
    <div class="adm-card-head"><h2>Judul paling banyak disimpan</h2></div>
    <?php if (!$top): ?>
      <p class="adm-empty">Daftar muncul setelah ada pengguna yang menambahkan judul ke My List.</p>
    <?php else: ?>
      <ol class="adm-top">
        <?php foreach ($top as $t): ?>
          <li>
            <img src="<?= e(image_url($t['image_url'])) ?>" alt="" loading="lazy" width="38" height="54">
            <div>
              <a href="<?= e(media_detail_url((string) $t['media_type'], (int) $t['external_id'], 0)) ?>"><?= e($t['title']) ?></a>
              <small><?= e(ucfirst((string) $t['media_type'])) ?><?= $t['avg_score'] !== null ? ' · rata-rata skor ' . number_format((float) $t['avg_score'], 1) : '' ?></small>
            </div>
            <b><?= (int) $t['total'] ?><small>pengguna</small></b>
          </li>
        <?php endforeach; ?>
      </ol>
    <?php endif; ?>
  </div>
</div>

<div class="adm-card">
  <div class="adm-card-head"><h2>Aktivitas terbaru</h2></div>
  <?php if (!$activity): ?>
    <p class="adm-empty">Belum ada aktivitas.</p>
  <?php else: ?>
    <ul class="adm-feed">
      <?php foreach ($activity as $a):
          $icon = ['user' => 'fa-user-plus', 'thread' => 'fa-comment', 'reply' => 'fa-reply', 'list' => 'fa-bookmark'][$a['kind']] ?? 'fa-circle';
          $href = match ($a['kind']) {
              'thread', 'reply' => url('thread&id=' . (int) $a['ref_id']),
              'user' => admin_url(['section' => 'users', 'q' => $a['actor']]),
              default => '',
          };
      ?>
        <li>
          <i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i>
          <p>
            <strong><?= e($a['actor']) ?></strong>
            <?php if ($a['kind'] === 'user'): ?> bergabung
            <?php elseif ($a['kind'] === 'thread'): ?> membuat thread <a href="<?= e($href) ?>"><?= e($a['label']) ?></a>
            <?php elseif ($a['kind'] === 'reply'): ?> membalas <a href="<?= e($href) ?>"><?= e($a['label']) ?></a>
            <?php else: ?> menyimpan <em><?= e($a['label']) ?></em> ke My List
            <?php endif; ?>
          </p>
          <time datetime="<?= e((string) $a['created_at']) ?>" title="<?= e((string) $a['created_at']) ?>"><?= e(admin_ago((string) $a['created_at'])) ?></time>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
