<?php
/** @var array $pg @var array|null $edit @var array $filters */
$f = $filters;
$from = $pg['total'] ? ($pg['page'] - 1) * $pg['per'] + 1 : 0;
$to = $from ? $from + count($pg['rows']) - 1 : 0;
$meId = (int) current_user()['id'];
?>
<div class="adm-toolbar">
  <form class="adm-filter" method="get" action="<?= e(asset_url('index.php')) ?>" role="search">
    <input type="hidden" name="page" value="admin"><input type="hidden" name="section" value="users">
    <label class="adm-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
      <input type="search" name="q" value="<?= e($f['q']) ?>" placeholder="Cari username atau email" aria-label="Cari pengguna"></label>
    <select name="role" aria-label="Filter role">
      <option value="">Semua role</option>
      <option value="admin" <?= $f['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
      <option value="user" <?= $f['role'] === 'user' ? 'selected' : '' ?>>User</option>
    </select>
    <button class="btn btn-sm btn-outline-light" type="submit">Terapkan</button>
    <?php if ($f['q'] !== '' || $f['role'] !== ''): ?><a class="adm-reset" href="<?= e(admin_url(['section' => 'users'])) ?>">Reset</a><?php endif; ?>
  </form>
</div>

<details class="adm-form" <?= $edit ? 'open' : '' ?>>
  <summary class="btn btn-primary btn-sm"><i class="fa-solid <?= $edit ? 'fa-pen' : 'fa-plus' ?>" aria-hidden="true"></i> <?= $edit ? 'Edit pengguna: ' . e($edit['username']) : 'Tambah pengguna' ?></summary>
  <form method="post" class="adm-fields">
    <?= admin_csrf_field() ?><?= admin_back_field() ?>
    <input type="hidden" name="action" value="admin_user_save">
    <input type="hidden" name="user_id" value="<?= (int) ($edit['id'] ?? 0) ?>">
    <label>Username<input name="username" required maxlength="50" pattern="[A-Za-z0-9_]{3,50}" title="3-50 karakter: huruf, angka, underscore" value="<?= e($edit['username'] ?? '') ?>"></label>
    <label>Email<input type="email" name="email" required maxlength="150" value="<?= e($edit['email'] ?? '') ?>"></label>
    <label>Role
      <select name="role">
        <option value="user" <?= ($edit['role'] ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
        <option value="admin" <?= ($edit['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
      </select>
    </label>
    <label>Password<input type="password" name="password" minlength="6" autocomplete="new-password" <?= $edit ? '' : 'required' ?> placeholder="<?= $edit ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"></label>
    <div class="adm-fields-actions">
      <button class="btn btn-primary btn-sm" type="submit">Simpan</button>
      <?php if ($edit): ?><a class="btn btn-outline-light btn-sm" href="<?= e(admin_url(['section' => 'users'] + array_filter($f))) ?>">Batal</a><?php endif; ?>
    </div>
  </form>
</details>

<div class="adm-card adm-card-flush">
  <?php if (!$pg['rows']): ?>
    <p class="adm-empty">Tidak ada pengguna yang cocok. <a href="<?= e(admin_url(['section' => 'users'])) ?>">Tampilkan semua</a></p>
  <?php else: ?>
  <div class="adm-table-wrap">
    <table class="adm-table">
      <thead><tr><th>Pengguna</th><th>Role</th><th class="num">My List</th><th class="num">Thread</th><th>Bergabung</th><th class="end"><span class="visually-hidden">Aksi</span></th></tr></thead>
      <tbody>
      <?php foreach ($pg['rows'] as $u): $isMe = (int) $u['id'] === $meId; ?>
        <tr>
          <td data-label="Pengguna">
            <div class="adm-person">
              <span class="mini-avatar"><?= e(strtoupper(substr($u['username'], 0, 1))) ?></span>
              <div><strong><?= e($u['username']) ?></strong><?php if ($isMe): ?><span class="adm-badge is-muted">Kamu</span><?php endif; ?><small><?= e($u['email']) ?></small></div>
            </div>
          </td>
          <td data-label="Role"><span class="adm-badge <?= $u['role'] === 'admin' ? 'is-admin' : '' ?>"><?= $u['role'] === 'admin' ? 'Admin' : 'User' ?></span></td>
          <td class="num" data-label="My List"><?= (int) $u['list_count'] ?></td>
          <td class="num" data-label="Thread"><?= (int) $u['thread_count'] ?></td>
          <td data-label="Bergabung"><time title="<?= e((string) $u['created_at']) ?>"><?= e(admin_ago((string) $u['created_at'])) ?></time></td>
          <td class="end">
            <div class="adm-actions">
              <a class="btn btn-sm btn-outline-light" href="<?= e(admin_url(['section' => 'users', 'edit' => (int) $u['id']] + array_filter($f))) ?>"><i class="fa-solid fa-pen" aria-hidden="true"></i> Edit</a>
              <?php if (!$isMe): ?>
              <form method="post" data-confirm="Hapus pengguna <?= e($u['username']) ?>? List dan thread forum miliknya ikut terhapus. Tindakan ini tidak bisa dibatalkan.">
                <?= admin_csrf_field() ?><?= admin_back_field() ?>
                <input type="hidden" name="action" value="admin_user_delete"><input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Hapus</button>
              </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="adm-tablefoot"><span>Menampilkan <?= $from ?>–<?= $to ?> dari <?= (int) $pg['total'] ?> pengguna</span><?= admin_pager($pg, ['section' => 'users'] + array_filter($f)) ?></div>
  <?php endif; ?>
</div>
