<?php
/** @var array $rows @var array|null $edit */
?>
<details class="adm-form" <?= $edit ? 'open' : '' ?>>
  <summary class="btn btn-primary btn-sm"><i class="fa-solid <?= $edit ? 'fa-pen' : 'fa-plus' ?>" aria-hidden="true"></i> <?= $edit ? 'Edit kategori: ' . e($edit['name']) : 'Tambah kategori' ?></summary>
  <form method="post" class="adm-fields">
    <?= admin_csrf_field() ?><?= admin_back_field() ?>
    <input type="hidden" name="action" value="admin_category_save">
    <input type="hidden" name="category_id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <label>Nama<input name="name" required maxlength="80" value="<?= e($edit['name'] ?? '') ?>"></label>
    <label class="wide">Deskripsi<input name="description" maxlength="255" value="<?= e($edit['description'] ?? '') ?>" placeholder="Satu kalimat tentang topik kategori ini"></label>
    <div class="adm-fields-actions">
      <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
      <?php if ($edit): ?><a class="btn btn-outline-light btn-sm" href="<?= e(admin_url(['section' => 'categories'])) ?>">Batal</a><?php endif; ?>
    </div>
  </form>
</details>

<div class="adm-card adm-card-flush">
  <?php if (!$rows): ?>
    <p class="adm-empty">Belum ada kategori. Tanpa kategori, pengguna tidak bisa membuat thread.</p>
  <?php else: ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Kategori</th><th>Deskripsi</th><th class="num">Thread</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td data-label="Kategori"><strong><?= e($row['name']) ?></strong></td>
          <td data-label="Deskripsi" class="adm-wrap"><?= e($row['description'] ?: '—') ?></td>
          <td class="num" data-label="Thread"><?= (int) $row['thread_count'] ?></td>
          <td class="end">
            <div class="adm-actions">
              <a class="btn btn-sm btn-outline-light" href="<?= e(admin_url(['section' => 'categories', 'edit' => (int) $row['id']])) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              <?php if ((int) $row['thread_count'] === 0): ?>
              <form method="post" data-confirm="Hapus kategori <?= e($row['name']) ?>?">
                <?= admin_csrf_field() ?><?= admin_back_field() ?>
                <input type="hidden" name="action" value="admin_category_delete"><input type="hidden" name="category_id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
              </form>
              <?php else: ?>
              <span class="adm-hint" title="Kategori yang masih punya thread tidak bisa dihapus">Dipakai thread</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
