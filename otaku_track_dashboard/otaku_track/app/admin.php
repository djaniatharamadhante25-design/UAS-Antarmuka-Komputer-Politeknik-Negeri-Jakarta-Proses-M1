<?php
declare(strict_types=1);

/**
 * Lapisan data + aksi untuk dashboard admin (Kelola Data).
 * Semua query memakai SQL standar (tanpa fungsi khusus MySQL) dan prepared statement.
 */

const ADMIN_SECTIONS = ['overview', 'users', 'links', 'categories', 'forum'];
const ADMIN_PER_PAGE = 15;

/* ---------------------------------------------------------------- helper URL */

function admin_section(): string
{
    $s = (string) get('section', 'overview');
    return in_array($s, ADMIN_SECTIONS, true) ? $s : 'overview';
}

function admin_url(array $params = []): string
{
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return url('admin&' . http_build_query($params));
}

/** Query string halaman admin yang sedang dibuka (tanpa page/edit/p) supaya aksi bisa kembali ke tampilan yang sama. */
function admin_current_query(): string
{
    $q = $_GET;
    unset($q['page'], $q['edit'], $q['p']);
    $q['section'] = admin_section();
    return http_build_query($q);
}

function admin_back_field(): string
{
    return '<input type="hidden" name="back" value="' . e(admin_current_query()) . '">';
}

function admin_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** Tujuan redirect setelah aksi. Hanya menerima query admin yang bentuknya aman. */
function admin_back(string $default): string
{
    $back = (string) post('back', '');
    if (preg_match('/^section=(overview|users|links|categories|forum)(&[A-Za-z0-9_%.=+\-]*)?$/', $back)) {
        return 'admin&' . $back;
    }
    return 'admin&' . $default;
}

/* ------------------------------------------------------------ helper tampilan */

function admin_date(int $t, bool $withYear = true): string
{
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return date('j', $t) . ' ' . $bulan[(int) date('n', $t) - 1] . ($withYear ? ' ' . date('Y', $t) : '');
}

function admin_ago(?string $ts): string
{
    if (!$ts) return '—';
    $t = strtotime($ts);
    if ($t === false) return e($ts);
    $d = time() - $t;
    if ($d < 60) return 'baru saja';
    if ($d < 3600) return (int) ($d / 60) . ' mnt lalu';
    if ($d < 86400) return (int) ($d / 3600) . ' jam lalu';
    if ($d < 86400 * 7) return (int) ($d / 86400) . ' hari lalu';
    return admin_date($t);
}

function admin_like(string $q): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
}

function admin_status_label(string $status): string
{
    return [
        'watching' => 'Sedang ditonton', 'completed' => 'Selesai', 'on_hold' => 'Ditunda',
        'dropped' => 'Dihentikan', 'plan_to_watch' => 'Rencana nonton',
        'reading' => 'Sedang dibaca', 'plan_to_read' => 'Rencana baca',
    ][$status] ?? format_status($status);
}

/** Pagination sederhana. $params = filter aktif (tanpa p). */
function admin_pager(array $pg, array $params): string
{
    if ($pg['pages'] <= 1) return '';
    $cur = $pg['page'];
    $last = $pg['pages'];
    $nums = array_unique(array_filter([1, $cur - 1, $cur, $cur + 1, $last], static fn($n) => $n >= 1 && $n <= $last));
    sort($nums);

    $html = '<nav class="adm-pager" aria-label="Halaman">';
    $link = static function (int $n, string $label, bool $disabled = false, bool $active = false) use ($params): string {
        if ($disabled) return '<span class="adm-page is-disabled">' . $label . '</span>';
        $href = e(admin_url($params + ['p' => $n]));
        return '<a class="adm-page' . ($active ? ' is-active' : '') . '" href="' . $href . '"' . ($active ? ' aria-current="page"' : '') . '>' . $label . '</a>';
    };
    $html .= $link($cur - 1, '&lsaquo; Sebelumnya', $cur <= 1);
    $prev = 0;
    foreach ($nums as $n) {
        if ($prev && $n - $prev > 1) $html .= '<span class="adm-gap">…</span>';
        $html .= $link($n, (string) $n, false, $n === $cur);
        $prev = $n;
    }
    $html .= $link($cur + 1, 'Berikutnya &rsaquo;', $cur >= $last);
    return $html . '</nav>';
}

/**
 * Grafik batang berpasangan (SVG, dibuat di server). Warna lewat CSS supaya ikut tema.
 * @param string[] $labels  label sumbu x (Y-m-d)
 */
function admin_chart(array $labels, array $a, array $b, string $nameA, string $nameB): string
{
    $w = 640; $h = 230; $pl = 30; $pr = 6; $pt = 10; $pb = 28;
    $plotW = $w - $pl - $pr;
    $plotH = $h - $pt - $pb;
    $max = max(4, ...$a, ...$b);
    $max = (int) (ceil($max / 4) * 4);
    $n = count($labels);
    $slot = $plotW / $n;
    $barW = min(13, $slot * 0.34);

    $svg = '<svg class="adm-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="Grafik harian: ' . e($nameA) . ' dan ' . e($nameB) . ' selama ' . $n . ' hari terakhir">';
    for ($i = 0; $i <= 4; $i++) {
        $y = $pt + $plotH - ($plotH * $i / 4);
        $svg .= '<line class="c-grid" x1="' . $pl . '" x2="' . ($w - $pr) . '" y1="' . round($y, 1) . '" y2="' . round($y, 1) . '"/>';
        $svg .= '<text class="c-txt" x="' . ($pl - 6) . '" y="' . round($y + 3.5, 1) . '" text-anchor="end">' . (int) ($max * $i / 4) . '</text>';
    }
    foreach ($labels as $i => $day) {
        $cx = $pl + $slot * $i + $slot / 2;
        $ts = strtotime($day);
        $tip = admin_date($ts, false);
        foreach ([[$a[$i], 'c-a', -$barW - 1, $nameA], [$b[$i], 'c-b', 1, $nameB]] as [$v, $cls, $dx, $nm]) {
            $bh = $v > 0 ? max(2.5, $plotH * $v / $max) : 0;
            if ($bh > 0) {
                $svg .= '<rect class="' . $cls . '" x="' . round($cx + $dx, 1) . '" y="' . round($pt + $plotH - $bh, 1) . '" width="' . round($barW, 1) . '" height="' . round($bh, 1) . '" rx="2"><title>' . e($tip . ' · ' . $v . ' ' . $nm) . '</title></rect>';
            }
        }
        if ($i % 2 === 1 || $i === $n - 1) {
            $svg .= '<text class="c-txt" x="' . round($cx, 1) . '" y="' . ($h - 8) . '" text-anchor="middle">' . date('j', $ts) . '</text>';
        }
    }
    return $svg . '</svg>';
}

/* ---------------------------------------------------------------- data (GET) */

function admin_paged(string $from, string $select, array $params, string $order): array
{
    $per = ADMIN_PER_PAGE;
    $stmt = db()->prepare("SELECT COUNT(*) FROM $from");
    $stmt->execute($params);
    $total = (int) $stmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $per));
    $page = min($pages, max(1, (int) get('p', 1)));
    $offset = ($page - 1) * $per;

    $stmt = db()->prepare("SELECT $select FROM $from ORDER BY $order LIMIT $per OFFSET $offset");
    $stmt->execute($params);
    return ['rows' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per];
}

function admin_counts(): array
{
    $week = date('Y-m-d H:i:s', strtotime('-7 days'));
    $stmt = db()->prepare(
        'SELECT
            (SELECT COUNT(*) FROM users) users,
            (SELECT COUNT(*) FROM users WHERE created_at >= ?) users_week,
            (SELECT COUNT(*) FROM users WHERE role=\'admin\') admins,
            (SELECT COUNT(*) FROM media_lists) lists,
            (SELECT COUNT(*) FROM media_lists WHERE created_at >= ?) lists_week,
            (SELECT COUNT(*) FROM forum_threads) threads,
            (SELECT COUNT(*) FROM forum_threads WHERE created_at >= ?) threads_week,
            (SELECT COUNT(*) FROM forum_threads WHERE is_hidden=1) threads_hidden,
            (SELECT COUNT(*) FROM forum_threads WHERE is_locked=1) threads_locked,
            (SELECT COUNT(*) FROM forum_replies) replies,
            (SELECT COUNT(*) FROM forum_replies WHERE created_at >= ?) replies_week,
            (SELECT COUNT(*) FROM forum_replies WHERE is_hidden=1) replies_hidden,
            (SELECT COUNT(*) FROM watch_links) links,
            (SELECT COUNT(*) FROM forum_categories) categories'
    );
    $stmt->execute([$week, $week, $week, $week]);
    return array_map('intval', $stmt->fetch());
}

function admin_daily(string $table, string $since): array
{
    // $table hanya diisi konstanta internal di bawah, bukan input pengguna.
    $stmt = db()->prepare("SELECT DATE(created_at) d, COUNT(*) c FROM $table WHERE created_at >= ? GROUP BY DATE(created_at)");
    $stmt->execute([$since]);
    $map = [];
    foreach ($stmt as $row) $map[(string) $row['d']] = (int) $row['c'];
    return $map;
}

function admin_overview(): array
{
    $days = [];
    for ($i = 13; $i >= 0; $i--) $days[] = date('Y-m-d', strtotime("-$i days"));
    $since = $days[0] . ' 00:00:00';
    $u = admin_daily('users', $since);
    $l = admin_daily('media_lists', $since);

    $statuses = db()->query('SELECT status, COUNT(*) total FROM media_lists GROUP BY status ORDER BY total DESC')->fetchAll();

    $top = db()->query(
        'SELECT media_type, external_id, MAX(title) title, MAX(image_url) image_url, COUNT(*) total, AVG(score) avg_score
         FROM media_lists GROUP BY media_type, external_id ORDER BY total DESC, title LIMIT 5'
    )->fetchAll();

    $activity = db()->query(
        "SELECT * FROM (
            SELECT 'user' kind, username actor, '' label, id ref_id, created_at FROM users
            UNION ALL
            SELECT 'thread', u.username, t.title, t.id, t.created_at FROM forum_threads t JOIN users u ON u.id=t.user_id
            UNION ALL
            SELECT 'reply', u.username, t.title, t.id, r.created_at FROM forum_replies r JOIN users u ON u.id=r.user_id JOIN forum_threads t ON t.id=r.thread_id
            UNION ALL
            SELECT 'list', u.username, m.title, m.external_id, m.created_at FROM media_lists m JOIN users u ON u.id=m.user_id
         ) a ORDER BY created_at DESC LIMIT 8"
    )->fetchAll();

    return [
        'chart_days' => $days,
        'chart_users' => array_map(static fn($d) => $u[$d] ?? 0, $days),
        'chart_lists' => array_map(static fn($d) => $l[$d] ?? 0, $days),
        'statuses' => $statuses,
        'top' => $top,
        'activity' => $activity,
    ];
}

function admin_users(): array
{
    $q = trim((string) get('q', ''));
    $role = (string) get('role', '');
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        $where[] = "(username LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
        $params[] = $params[] = admin_like($q);
    }
    if (in_array($role, ['user', 'admin'], true)) {
        $where[] = 'role=?';
        $params[] = $role;
    }
    $pg = admin_paged(
        'users WHERE ' . implode(' AND ', $where),
        'id, username, email, role, created_at,
         (SELECT COUNT(*) FROM media_lists m WHERE m.user_id=users.id) list_count,
         (SELECT COUNT(*) FROM forum_threads t WHERE t.user_id=users.id) thread_count',
        $params,
        'created_at DESC, id DESC'
    );
    $edit = null;
    $editId = (int) get('edit', 0);
    if ($editId > 0) {
        $stmt = db()->prepare('SELECT id, username, email, role FROM users WHERE id=?');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch() ?: null;
    }
    return ['pg' => $pg, 'edit' => $edit, 'filters' => ['q' => $q, 'role' => $role]];
}

function admin_links(): array
{
    $q = trim((string) get('q', ''));
    $type = (string) get('type', '');
    $where = ['1=1'];
    $params = [];
    if ($q !== '') {
        if (ctype_digit($q)) {
            $where[] = "(w.external_id=? OR w.provider LIKE ? ESCAPE '!')";
            $params[] = (int) $q;
            $params[] = admin_like($q);
        } else {
            $where[] = "(w.provider LIKE ? ESCAPE '!' OR w.url LIKE ? ESCAPE '!')";
            $params[] = $params[] = admin_like($q);
        }
    }
    if (in_array($type, ['anime', 'manga'], true)) {
        $where[] = 'w.media_type=?';
        $params[] = $type;
    }
    $pg = admin_paged(
        'watch_links w LEFT JOIN users u ON u.id=w.created_by WHERE ' . implode(' AND ', $where),
        'w.id, w.external_id, w.media_type, w.provider, w.url, w.created_at, u.username',
        $params,
        'w.created_at DESC, w.id DESC'
    );
    $edit = null;
    $editId = (int) get('edit', 0);
    if ($editId > 0) {
        $stmt = db()->prepare('SELECT id, external_id, media_type, provider, url FROM watch_links WHERE id=?');
        $stmt->execute([$editId]);
        $edit = $stmt->fetch() ?: null;
    }
    return ['pg' => $pg, 'edit' => $edit, 'filters' => ['q' => $q, 'type' => $type]];
}

function admin_categories(): array
{
    $rows = db()->query(
        'SELECT c.id, c.name, c.description, COUNT(t.id) thread_count
         FROM forum_categories c LEFT JOIN forum_threads t ON t.category_id=c.id
         GROUP BY c.id, c.name, c.description ORDER BY c.id'
    )->fetchAll();
    $edit = null;
    $editId = (int) get('edit', 0);
    if ($editId > 0) {
        foreach ($rows as $r) if ((int) $r['id'] === $editId) { $edit = $r; break; }
    }
    return ['rows' => $rows, 'edit' => $edit];
}

function admin_forum(): array
{
    $tab = get('tab') === 'replies' ? 'replies' : 'threads';
    $q = trim((string) get('q', ''));
    $filter = (string) get('filter', '');
    $params = [];

    if ($tab === 'threads') {
        $where = ['1=1'];
        if ($q !== '') {
            $where[] = "t.title LIKE ? ESCAPE '!'";
            $params[] = admin_like($q);
        }
        if ($filter === 'hidden') $where[] = 't.is_hidden=1';
        elseif ($filter === 'locked') $where[] = 't.is_locked=1';
        else $filter = '';
        $pg = admin_paged(
            'forum_threads t JOIN users u ON u.id=t.user_id JOIN forum_categories c ON c.id=t.category_id WHERE ' . implode(' AND ', $where),
            't.id, t.title, t.is_locked, t.is_hidden, t.created_at, u.username, c.name category_name,
             (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id=t.id) reply_count',
            $params,
            't.created_at DESC, t.id DESC'
        );
    } else {
        $where = ['1=1'];
        if ($q !== '') {
            $where[] = "r.body LIKE ? ESCAPE '!'";
            $params[] = admin_like($q);
        }
        if ($filter === 'hidden') $where[] = 'r.is_hidden=1';
        else $filter = '';
        $pg = admin_paged(
            'forum_replies r JOIN users u ON u.id=r.user_id JOIN forum_threads t ON t.id=r.thread_id WHERE ' . implode(' AND ', $where),
            'r.id, r.body, r.is_hidden, r.created_at, u.username, t.id thread_id, t.title thread_title',
            $params,
            'r.created_at DESC, r.id DESC'
        );
    }
    return ['pg' => $pg, 'tab' => $tab, 'filters' => ['tab' => $tab, 'q' => $q, 'filter' => $filter]];
}

function admin_data(string $section): array
{
    $data = ['section' => $section, 'counts' => admin_counts()];
    return $data + match ($section) {
        'users' => admin_users(),
        'links' => admin_links(),
        'categories' => admin_categories(),
        'forum' => admin_forum(),
        default => admin_overview(),
    };
}

/* --------------------------------------------------------------- aksi (POST) */

function admin_is_http_url(string $url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && preg_match('#^https?://#i', $url) === 1
        && strlen($url) <= 1000;
}

function admin_admin_count(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn();
}

function admin_handle_post(string $action): void
{
    require_admin();
    $me = (int) current_user()['id'];

    switch ($action) {
        case 'admin_user_save':
            $id = (int) post('user_id', 0);
            $username = trim((string) post('username', ''));
            $email = trim((string) post('email', ''));
            $role = post('role') === 'admin' ? 'admin' : 'user';
            $password = (string) post('password', '');
            $back = admin_back('section=users');

            if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Username (3-50 huruf/angka/underscore) atau email tidak valid.');
                redirect($back);
            }
            if ($password !== '' && strlen($password) < 6) {
                flash('error', 'Password minimal 6 karakter.');
                redirect($back);
            }
            try {
                if ($id > 0) {
                    if ($id === $me && $role !== 'admin') {
                        flash('error', 'Role akun yang sedang kamu pakai tidak bisa diturunkan.');
                        redirect($back);
                    }
                    if ($password !== '') {
                        db()->prepare('UPDATE users SET username=?, email=?, role=?, password_hash=? WHERE id=?')
                            ->execute([$username, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        db()->prepare('UPDATE users SET username=?, email=?, role=? WHERE id=?')
                            ->execute([$username, $email, $role, $id]);
                    }
                    flash('success', 'Data pengguna diperbarui.');
                } else {
                    if ($password === '') {
                        flash('error', 'Password wajib diisi untuk pengguna baru.');
                        redirect($back);
                    }
                    db()->prepare('INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,?)')
                        ->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
                    flash('success', 'Pengguna ' . $username . ' ditambahkan.');
                }
            } catch (PDOException $e) {
                flash('error', 'Username atau email sudah dipakai pengguna lain.');
            }
            redirect(admin_back('section=users'));

        case 'admin_user_delete':
            $id = (int) post('user_id', 0);
            if ($id === $me) {
                flash('error', 'Akun yang sedang kamu pakai tidak bisa dihapus.');
            } else {
                $stmt = db()->prepare('SELECT role FROM users WHERE id=?');
                $stmt->execute([$id]);
                $target = $stmt->fetch();
                if (!$target) {
                    flash('error', 'Pengguna tidak ditemukan.');
                } elseif ($target['role'] === 'admin' && admin_admin_count() <= 1) {
                    flash('error', 'Admin terakhir tidak bisa dihapus.');
                } else {
                    db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
                    flash('success', 'Pengguna dihapus beserta list dan postingan forum miliknya.');
                }
            }
            redirect(admin_back('section=users'));

        case 'admin_watch_save':
            $id = (int) post('watch_id', 0);
            $externalId = (int) post('external_id', 0);
            $type = post('media_type') === 'manga' ? 'manga' : 'anime';
            $provider = trim((string) post('provider', ''));
            $link = trim((string) post('url', ''));
            if ($externalId < 1 || $provider === '' || strlen($provider) > 100 || !admin_is_http_url($link)) {
                flash('error', 'Data link tidak valid. URL harus diawali http:// atau https://.');
                redirect(admin_back('section=links'));
            }
            if ($id > 0) {
                db()->prepare('UPDATE watch_links SET external_id=?, media_type=?, provider=?, url=? WHERE id=?')
                    ->execute([$externalId, $type, $provider, $link, $id]);
                flash('success', 'Link diperbarui.');
            } else {
                db()->prepare('INSERT INTO watch_links (external_id,media_type,provider,url,created_by) VALUES (?,?,?,?,?)')
                    ->execute([$externalId, $type, $provider, $link, $me]);
                flash('success', 'Link ditambahkan.');
            }
            redirect(admin_back('section=links'));

        case 'admin_watch_delete':
            db()->prepare('DELETE FROM watch_links WHERE id=?')->execute([(int) post('watch_id', 0)]);
            flash('success', 'Link dihapus.');
            redirect(admin_back('section=links'));

        case 'admin_category_save':
            $id = (int) post('category_id', 0);
            $name = trim((string) post('name', ''));
            $desc = trim((string) post('description', ''));
            if ($name === '' || strlen($name) > 80 || strlen($desc) > 255) {
                flash('error', 'Nama kategori wajib diisi (maks. 80 karakter), deskripsi maks. 255 karakter.');
                redirect(admin_back('section=categories'));
            }
            try {
                if ($id > 0) {
                    db()->prepare('UPDATE forum_categories SET name=?, description=? WHERE id=?')->execute([$name, $desc ?: null, $id]);
                    flash('success', 'Kategori diperbarui.');
                } else {
                    db()->prepare('INSERT INTO forum_categories (name,description) VALUES (?,?)')->execute([$name, $desc ?: null]);
                    flash('success', 'Kategori ditambahkan.');
                }
            } catch (PDOException $e) {
                flash('error', 'Nama kategori sudah dipakai.');
            }
            redirect(admin_back('section=categories'));

        case 'admin_category_delete':
            $id = (int) post('category_id', 0);
            $stmt = db()->prepare('SELECT COUNT(*) FROM forum_threads WHERE category_id=?');
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0) {
                flash('error', 'Kategori masih punya thread. Pindahkan atau hapus thread-nya dulu.');
            } else {
                db()->prepare('DELETE FROM forum_categories WHERE id=?')->execute([$id]);
                flash('success', 'Kategori dihapus.');
            }
            redirect(admin_back('section=categories'));

        case 'admin_thread_hide':
            db()->prepare('UPDATE forum_threads SET is_hidden=1-is_hidden WHERE id=?')->execute([(int) post('thread_id')]);
            flash('success', 'Visibilitas thread diubah.');
            redirect(admin_back('section=forum'));

        case 'admin_thread_lock':
            db()->prepare('UPDATE forum_threads SET is_locked=1-is_locked WHERE id=?')->execute([(int) post('thread_id')]);
            flash('success', 'Status kunci thread diubah.');
            redirect(admin_back('section=forum'));

        case 'admin_reply_hide':
            db()->prepare('UPDATE forum_replies SET is_hidden=1-is_hidden WHERE id=?')->execute([(int) post('reply_id')]);
            flash('success', 'Visibilitas balasan diubah.');
            redirect(admin_back('section=forum&tab=replies'));
    }

    http_response_code(400);
    exit('Aksi admin tidak dikenal.');
}
