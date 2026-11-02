<?php
/** @var array $pg @var array|null $edit @var array $filters */
$f = $filters;
$from = $pg['total'] ? ($pg['page'] - 1) * $pg['per'] + 1 : 0;
$to = $from ? $from + count($pg['rows']) - 1 : 0;
?>
<div class="adm-toolbar">
  <form class="adm-filter" method="get" action="<?= e(asset_url('index.php')) ?>" role="search">
    <input type="hidden" name="page" value="admin"><input type="hidden" name="section" value="links">
    <label class="adm-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari MAL ID, provider, atau URL" aria-label="Cari link"></label>
    <select name="type" aria-label="Filter tipe">
      <option value="">Anime dan manga</option>
      <option value="anime" <?= $f['type'] === 'anime' ? 'selected' : '' ?>>Anime</option>
      <option value="manga" <?= $f['type'] === 'manga' ? 'selected' : '' ?>>Manga</option>
    </select>
    <button class="btn btn-sm btn-outline-light" type="submit">Terapkan</button>
    <?php if ($f['q'] !== '' || $f['type'] !== ''): ?><a class="adm-reset" href="<?= e(admin_url(['section' => 'links'])) ?>">Reset</a><?php endif; ?>
  </form>
</div>

<details class="adm-form" <?= $edit ? 'open' : '' ?>>
  <summary class="btn btn-primary btn-sm"><i class="fa-solid <?= $edit ? 'fa-pen' : 'fa-plus' ?>" aria-hidden="true"></i> <?= $edit ? 'Edit link #' . (int) $edit['id'] : 'Tambah link' ?></summary>
  <form method="post" class="adm-fields">
    <?= admin_csrf_field() ?><?= admin_back_field() ?>
    <input type="hidden" name="action" value="admin_watch_save">
    <input type="hidden" name="watch_id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <label>MAL ID<input type="number" min="1" name="external_id" required value="<?= (int) ($edit['external_id'] ?? 0) ?: '' ?>"><small>Angka di akhir URL MyAnimeList, misalnya 5114.</small></label>
    <label>Tipe
      <select name="media_type">
        <option value="anime" <?= ($edit['media_type'] ?? 'anime') === 'anime' ? 'selected' : '' ?>>Anime</option>
        <option value="manga" <?= ($edit['media_type'] ?? '') === 'manga' ? 'selected' : '' ?>>Manga</option>
      </select>
    </label>
    <label>Provider<input name="provider" maxlength="100" required placeholder="Crunchyroll" value="<?= e($edit['provider'] ?? '') ?>"></label>
    <label class="wide">URL<input type="url" name="url" maxlength="1000" required placeholder="https://…" value="<?= e($edit['url'] ?? '') ?>"></label>
    <div class="adm-fields-actions">
      <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
      <?php if ($edit): ?><a class="btn btn-outline-light btn-sm" href="<?= e(admin_url(['section' => 'links'] + array_filter($f))) ?>">Batal</a><?php endif; ?>
    </div>
  </form>
</details>

<div class="adm-card adm-card-flush">
  <?php if (!$pg['rows']): ?>
    <p class="adm-empty"><?= ($f['q'] !== '' || $f['type'] !== '') ? 'Tidak ada link yang cocok.' : 'Belum ada link. Pilih “Tambah link” untuk menautkan anime atau manga ke platform resminya.' ?></p>
  <?php else: ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Judul (MAL ID)</th><th>Provider</th><th>URL</th><th>Ditambahkan</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($pg['rows'] as $row): ?>
        <tr>
          <td data-label="Judul">
            <a class="adm-idlink" href="<?= e(media_detail_url((string) $row['media_type'], (int) $row['external_id'], 0)) ?>" target="_blank" rel="noopener">
              #<?= (int) $row['external_id'] ?> <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span class="visually-hidden">buka halaman detail</span>
            </a>
            <span class="adm-badge"><?= e(ucfirst((string) $row['media_type'])) ?></span>
          </td>
          <td data-label="Provider"><strong><?= e($row['provider']) ?></strong></td>
          <td data-label="URL" class="adm-url"><a href="<?= e($row['url']) ?>" target="_blank" rel="noopener noreferrer" title="<?= e($row['url']) ?>"><?= e((string) parse_url((string) $row['url'], PHP_URL_HOST) . (parse_url((string) $row['url'], PHP_URL_PATH) ?: '')) ?></a></td>
          <td data-label="Ditambahkan"><?= e($row['username'] ?? '—') ?><small><?= e(admin_ago((string) $row['created_at'])) ?></small></td>
          <td class="end">
            <div class="adm-actions">
              <a class="btn btn-sm btn-outline-light" href="<?= e(admin_url(['section' => 'links', 'edit' => (int) $row['id']] + array_filter($f))) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              <form method="post" data-confirm="Hapus link <?= e($row['provider']) ?> untuk #<?= (int) $row['external_id'] ?>?">
                <?= admin_csrf_field() ?><?= admin_back_field() ?>
                <input type="hidden" name="action" value="admin_watch_delete"><input type="hidden" name="watch_id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="adm-tablefoot"><span>Menampilkan <?= $from ?>–<?= $to ?> dari <?= (int) $pg['total'] ?> link</span><?= admin_pager($pg, ['section' => 'links'] + array_filter($f)) ?></div>
  <?php endif; ?>
</div>
