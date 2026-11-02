<?php
/** @var string $section @var array $counts */
$me = current_user();
$nav = [
    'overview'   => ['Ringkasan',        'fa-chart-simple',   null],
    'users'      => ['Pengguna',         'fa-users',          $counts['users']],
    'links'      => ['Link tonton/baca', 'fa-link',           $counts['links']],
    'categories' => ['Kategori forum',   'fa-tags',           $counts['categories']],
    'forum'      => ['Moderasi forum',   'fa-shield-halved',  $counts['threads']],
];
$heads = [
    'overview'   => ['Ringkasan', 'Kondisi Otaku Track hari ini: pengguna, list, dan aktivitas forum.'],
    'users'      => ['Pengguna', 'Tambah akun, ubah role, atur ulang password, atau hapus akun.'],
    'links'      => ['Link tonton/baca', 'Link platform resmi yang tampil di bagian “Where to Watch” pada halaman detail.'],
    'categories' => ['Kategori forum', 'Kelompok topik tempat pengguna membuat thread.'],
    'forum'      => ['Moderasi forum', 'Sembunyikan atau kunci thread dan balasan yang melanggar aturan.'],
];
?>
<div class="container adm py-4">
  <div class="adm-shell">

    <aside class="adm-side" aria-label="Menu admin">
      <nav class="adm-nav">
        <?php foreach ($nav as $key => [$label, $icon, $badge]): ?>
          <a href="<?= e(admin_url(['section' => $key])) ?>" class="<?= $section === $key ? 'is-active' : '' ?>"<?= $section === $key ? ' aria-current="page"' : '' ?>>
            <i class="fa-solid <?= e($icon) ?>" aria-hidden="true"></i>
            <span><?= e($label) ?></span>
            <?php if ($badge !== null): ?><b class="adm-count"><?= (int) $badge ?></b><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <div class="adm-side-foot">
        <div class="adm-me">
          <span class="mini-avatar"><?= e(strtoupper(substr($me['username'], 0, 1))) ?></span>
          <div><strong><?= e($me['username']) ?></strong><small>Admin</small></div>
        </div>
        <a class="adm-back" href="<?= e(url('home')) ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali ke situs</a>
      </div>
    </aside>

    <section class="adm-main">
      <header class="adm-head">
        <h1><?= e($heads[$section][0]) ?></h1>
        <p><?= e($heads[$section][1]) ?></p>
      </header>
      <?php require __DIR__ . '/sections/' . $section . '.php'; ?>
    </section>

  </div>
</div>
