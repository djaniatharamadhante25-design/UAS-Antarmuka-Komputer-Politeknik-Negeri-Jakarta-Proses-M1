<?php

namespace App\Http\Controllers;

use App\Models\MediaList;
use App\Models\WatchLink;
use App\Support\AdminPresenter;
use App\Support\LocalCatalog;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Dashboard admin (Kelola Data) — diporta dari app/admin.php repo asli.
 * Data dipisah per section; aksi POST dikelompokkan di handleAction().
 */
final class AdminController extends Controller
{
    /** @var list<string> */
    public const SECTIONS = ['overview', 'users', 'links', 'categories', 'forum'];

    public function dashboard(Request $request): View
    {
        $requested = $request->route('section') ?? $request->query('section', 'overview');
        $section = in_array($requested, self::SECTIONS, true) ? $requested : 'overview';

        $data = array_merge([
            'counts' => $this->counts(),
            'toast' => null,
        ], match ($section) {
            'users' => $this->usersData($request),
            'links' => $this->linksData($request),
            'categories' => $this->categoriesData($request),
            'forum' => $this->forumData($request),
            default => $this->overviewData(),
        });

        $data['section'] = $section;

        return view('admin.dashboard', $data);
    }

    private function counts(): array
    {
        $week = now()->subDays(7)->toDateTimeString();

        $row = DB::selectOne(<<<'SQL'
            SELECT
                (SELECT COUNT(*) FROM users) AS users,
                (SELECT COUNT(*) FROM users WHERE created_at >= ?) AS users_week,
                (SELECT COUNT(*) FROM users WHERE role = 'admin') AS admins,
                (SELECT COUNT(*) FROM media_lists) AS lists,
                (SELECT COUNT(*) FROM media_lists WHERE created_at >= ?) AS lists_week,
                (SELECT COUNT(*) FROM forum_threads) AS threads,
                (SELECT COUNT(*) FROM forum_threads WHERE created_at >= ?) AS threads_week,
                (SELECT COUNT(*) FROM forum_threads WHERE is_hidden = 1) AS threads_hidden,
                (SELECT COUNT(*) FROM forum_threads WHERE is_locked = 1) AS threads_locked,
                (SELECT COUNT(*) FROM forum_replies) AS replies,
                (SELECT COUNT(*) FROM forum_replies WHERE created_at >= ?) AS replies_week,
                (SELECT COUNT(*) FROM forum_replies WHERE is_hidden = 1) AS replies_hidden,
                (SELECT COUNT(*) FROM watch_links) AS links,
                (SELECT COUNT(*) FROM forum_categories) AS categories
        SQL, [$week, $week, $week, $week]);

        $res = [];
        foreach (get_object_vars($row) as $k => $v) {
            $res[$k] = (int) $v;
        }
        $res['catalog_titles'] = LocalCatalog::count();

        return $res;
    }

    private function overviewData(): array
    {
        $days = [];
        for ($i = 13; $i >= 0; $i--) {
            $days[] = now()->subDays($i)->toDateString();
        }
        $since = $days[0].' 00:00:00';

        $usersDaily = $this->daily('users', $since);
        $listsDaily = $this->daily('media_lists', $since);

        $statuses = MediaList::query()
            ->selectRaw('status, COUNT(*) AS total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $top = MediaList::query()
            ->selectRaw('media_type, external_id, MAX(title) AS title, MAX(image_url) AS image_url, COUNT(*) AS total, AVG(score) AS avg_score')
            ->groupBy('media_type', 'external_id')
            ->orderByDesc('total')
            ->orderBy('title')
            ->limit(5)
            ->get();

        $activity = collect(DB::select(<<<'SQL'
            SELECT * FROM (
                SELECT 'user' AS kind, username AS actor, '' AS label, id AS ref_id, created_at FROM users
                UNION ALL
                SELECT 'thread', u.username, t.title, t.id, t.created_at FROM forum_threads t JOIN users u ON u.id = t.user_id
                UNION ALL
                SELECT 'reply', u.username, t.title, t.id, r.created_at FROM forum_replies r JOIN users u ON u.id = r.user_id JOIN forum_threads t ON t.id = r.thread_id
                UNION ALL
                SELECT 'list', u.username, m.title, m.external_id, m.created_at FROM media_lists m JOIN users u ON u.id = m.user_id
            ) a ORDER BY created_at DESC LIMIT 8
        SQL));

        return [
            'chart_days' => $days,
            'chart_users' => array_map(static fn ($d) => $usersDaily[$d] ?? 0, $days),
            'chart_lists' => array_map(static fn ($d) => $listsDaily[$d] ?? 0, $days),
            'statuses' => $statuses,
            'top' => $top,
            'activity' => $activity,
        ];
    }

    private function daily(string $table, string $since): array
    {
        // $table hanya dari konstanta internal di sini, bukan input pengguna.
        if (! in_array($table, ['users', 'media_lists'], true)) {
            return [];
        }

        $rows = DB::table($table)
            ->selectRaw('DATE(created_at) AS d, COUNT(*) AS c')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE(created_at)')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->d] = (int) $row->c;
        }

        return $map;
    }

    private function usersData(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $role = (string) $request->query('role', '');

        $query = DB::table('users')
            ->selectRaw('id, username, email, role, created_at,
                (SELECT COUNT(*) FROM media_lists m WHERE m.user_id = users.id) AS list_count,
                (SELECT COUNT(*) FROM forum_threads t WHERE t.user_id = users.id) AS thread_count');

        $where = [];
        $params = [];
        if ($q !== '') {
            $like = AdminPresenter::like($q);
            $where[] = "(username LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
            $params = [$like, $like];
        }
        if (in_array($role, ['user', 'admin'], true)) {
            $where[] = 'role = ?';
            $params[] = $role;
        }
        if ($where) {
            // Hanya pola LIKE hasil AdminPresenter::like() yang dimasukkan; nilai termasuk pilihan role tetap via binding.
            $query->whereRaw(implode(' AND ', $where), $params);
        }

        $pg = $this->paginate($query, AdminPresenter::perPage(), (int) $request->query('p', 1));

        $edit = null;
        $editId = (int) $request->query('edit', 0);
        if ($editId > 0) {
            $edit = DB::table('users')->where('id', $editId)->first(['id', 'username', 'email', 'role']);
        }

        return ['pg' => $pg, 'edit' => $edit, 'filters' => ['q' => $q, 'role' => $role]];
    }

    private function linksData(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', '');

        $query = DB::table('watch_links as w')
            ->selectRaw('w.id, w.external_id, w.media_type, w.provider, w.url, w.created_at, u.username')
            ->leftJoin('users as u', 'u.id', '=', 'w.created_by');

        $where = [];
        $params = [];
        if ($q !== '') {
            if (ctype_digit($q)) {
                $like = AdminPresenter::like($q);
                $where[] = "(w.external_id = ? OR w.provider LIKE ? ESCAPE '!')";
                $params = [(int) $q, $like];
            } else {
                $like = AdminPresenter::like($q);
                $where[] = "(w.provider LIKE ? ESCAPE '!' OR w.url LIKE ? ESCAPE '!')";
                $params = [$like, $like];
            }
        }
        if (in_array($type, ['anime', 'manga'], true)) {
            $where[] = 'w.media_type = ?';
            $params[] = $type;
        }
        if ($where) {
            $query->whereRaw(implode(' AND ', $where), $params);
        }
        $query->orderByDesc('w.created_at')->orderByDesc('w.id');

        $pg = $this->paginate($query, AdminPresenter::perPage(), (int) $request->query('p', 1));

        $edit = null;
        $editId = (int) $request->query('edit', 0);
        if ($editId > 0) {
            $edit = DB::table('watch_links')->where('id', $editId)->first(['id', 'external_id', 'media_type', 'provider', 'url']);
        }

        return ['pg' => $pg, 'edit' => $edit, 'filters' => ['q' => $q, 'type' => $type]];
    }

    private function categoriesData(Request $request): array
    {
        $rows = DB::table('forum_categories as c')
            ->selectRaw('c.id, c.name, c.description, COUNT(t.id) AS thread_count')
            ->leftJoin('forum_threads as t', 't.category_id', '=', 'c.id')
            ->groupBy('c.id', 'c.name', 'c.description')
            ->orderBy('c.id')
            ->get()
            ->all();

        $edit = null;
        $editId = (int) $request->query('edit', 0);
        if ($editId > 0) {
            foreach ($rows as $r) {
                if ((int) $r->id === $editId) {
                    $edit = $r;
                    break;
                }
            }
        }

        return ['rows' => $rows, 'edit' => $edit];
    }

    private function forumData(Request $request): array
    {
        $tab = $request->query('tab') === 'replies' ? 'replies' : 'threads';
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', '');
        $where = [];
        $params = [];

        if ($tab === 'threads') {
            $query = DB::table('forum_threads as t')
                ->selectRaw('t.id, t.title, t.is_locked, t.is_hidden, t.created_at, u.username, c.name AS category_name,
                    (SELECT COUNT(*) FROM forum_replies r WHERE r.thread_id = t.id) AS reply_count')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->join('forum_categories as c', 'c.id', '=', 't.category_id');
            if ($q !== '') {
                $where[] = "t.title LIKE ? ESCAPE '!'";
                $params[] = AdminPresenter::like($q);
            }
            if ($filter === 'hidden') {
                $where[] = 't.is_hidden = 1';
            } elseif ($filter === 'locked') {
                $where[] = 't.is_locked = 1';
            } else {
                $filter = '';
            }
            $query->whereRaw($where ? implode(' AND ', $where) : '1=1', $params);
            $query->orderByDesc('t.created_at')->orderByDesc('t.id');
        } else {
            $query = DB::table('forum_replies as r')
                ->selectRaw('r.id, r.body, r.is_hidden, r.created_at, u.username, t.id AS thread_id, t.title AS thread_title')
                ->join('users as u', 'u.id', '=', 'r.user_id')
                ->join('forum_threads as t', 't.id', '=', 'r.thread_id');
            if ($q !== '') {
                $where[] = "r.body LIKE ? ESCAPE '!'";
                $params[] = AdminPresenter::like($q);
            }
            if ($filter === 'hidden') {
                $where[] = 'r.is_hidden = 1';
            } else {
                $filter = '';
            }
            $query->whereRaw($where ? implode(' AND ', $where) : '1=1', $params);
            $query->orderByDesc('r.created_at')->orderByDesc('r.id');
        }

        $pg = $this->paginate($query, AdminPresenter::perPage(), (int) $request->query('p', 1));

        return ['pg' => $pg, 'tab' => $tab, 'filters' => ['tab' => $tab, 'q' => $q, 'filter' => $filter]];
    }

    /**
     * Paginasi manual (total + OFFSET) agar perilaku sama dengan admin_paged(),
     * dalam SQL yang sama, sehingga filter berbasis bind parameter tetap akurat.
     */
    private function paginate($query, int $per, int $page): array
    {
        $total = (clone $query)->get()->count();
        $pages = max(1, (int) ceil($total / $per));
        $page = min($pages, max(1, $page));
        $offset = ($page - 1) * $per;

        return [
            'rows' => $query->offset($offset)->limit($per)->get()->all(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per' => $per,
        ];
    }

    /* ------------------------------------------------------------ Aksi POST */

    public function handleAction(Request $request): RedirectResponse
    {
        $action = (string) $request->input('action', '');
        $me = (int) $request->user()->id;

        switch ($action) {
            case 'admin_user_save':
                return $this->saveUser($request, $me);
            case 'admin_user_delete':
                return $this->deleteUser($request, $me);
            case 'admin_watch_save':
                return $this->saveWatchLink($request, $me);
            case 'admin_watch_delete':
                return $this->deleteWatchLink($request);
            case 'admin_category_save':
                return $this->saveCategory($request);
            case 'admin_category_delete':
                return $this->deleteCategory($request);
            case 'admin_thread_hide':
                DB::table('forum_threads')->where('id', (int) $request->input('thread_id'))->update(['is_hidden' => DB::raw('1 - is_hidden')]);

                return redirect()->to($this->backUrl($request, 'section=forum'))->with('success', 'Visibilitas thread diubah.');
            case 'admin_thread_lock':
                DB::table('forum_threads')->where('id', (int) $request->input('thread_id'))->update(['is_locked' => DB::raw('1 - is_locked')]);

                return redirect()->to($this->backUrl($request, 'section=forum'))->with('success', 'Status kunci thread diubah.');
            case 'admin_reply_hide':
                DB::table('forum_replies')->where('id', (int) $request->input('reply_id'))->update(['is_hidden' => DB::raw('1 - is_hidden')]);

                return redirect()->to($this->backUrl($request, 'section=forum&tab=replies'))->with('success', 'Visibilitas balasan diubah.');
            default:
                abort(400, 'Aksi admin tidak dikenal.');
        }
    }

    private function saveUser(Request $request, int $me): RedirectResponse
    {
        $back = $this->backUrl($request, 'section=users');
        $id = (int) $request->input('user_id', 0);
        $username = trim((string) $request->input('username', ''));
        $email = trim((string) $request->input('email', ''));
        $role = $request->input('role') === 'admin' ? 'admin' : 'user';
        $password = (string) $request->input('password', '');

        if (! preg_match('/^[A-Za-z0-9_]{3,50}$/', $username) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->to($back)->with('error', 'Username (3-50 huruf/angka/underscore) atau email tidak valid.');
        }
        if ($password !== '' && strlen($password) < 6) {
            return redirect()->to($back)->with('error', 'Password minimal 6 karakter.');
        }

        try {
            if ($id > 0) {
                if ($id === $me && $role !== 'admin') {
                    return redirect()->to($back)->with('error', 'Role akun yang sedang kamu pakai tidak bisa diturunkan.');
                }
                $updates = ['username' => $username, 'email' => $email, 'role' => $role];
                if ($password !== '') {
                    $updates['password_hash'] = Hash::make($password);
                }
                DB::table('users')->where('id', $id)->update($updates);

                return redirect()->to($back)->with('success', 'Data pengguna diperbarui.');
            }

            if ($password === '') {
                return redirect()->to($back)->with('error', 'Password wajib diisi untuk pengguna baru.');
            }
            DB::table('users')->insert([
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($password),
                'role' => $role,
            ]);

            return redirect()->to($back)->with('success', 'Pengguna '.$username.' ditambahkan.');
        } catch (UniqueConstraintViolationException) {
            return redirect()->to($back)->with('error', 'Username atau email sudah dipakai pengguna lain.');
        }
    }

    private function deleteUser(Request $request, int $me): RedirectResponse
    {
        $back = $this->backUrl($request, 'section=users');
        $id = (int) $request->input('user_id', 0);

        if ($id === $me) {
            return redirect()->to($back)->with('error', 'Akun yang sedang kamu pakai tidak bisa dihapus.');
        }

        $target = DB::table('users')->where('id', $id)->first(['role']);

        if (! $target) {
            return redirect()->to($back)->with('error', 'Pengguna tidak ditemukan.');
        }
        if ($target->role === 'admin' && (int) DB::table('users')->where('role', 'admin')->count() <= 1) {
            return redirect()->to($back)->with('error', 'Admin terakhir tidak bisa dihapus.');
        }

        DB::table('users')->where('id', $id)->delete();

        return redirect()->to($back)->with('success', 'Pengguna dihapus beserta list dan postingan forum miliknya.');
    }

    private function saveWatchLink(Request $request, int $me): RedirectResponse
    {
        $back = $this->backUrl($request, 'section=links');
        $id = (int) $request->input('watch_id', 0);
        $externalId = (int) $request->input('external_id', 0);
        $type = $request->input('media_type') === 'manga' ? 'manga' : 'anime';
        $provider = trim((string) $request->input('provider', ''));
        $link = trim((string) $request->input('url', ''));

        if ($externalId < 1 || $provider === '' || strlen($provider) > 100 || ! $this->isHttpUrl($link)) {
            return redirect()->to($back)->with('error', 'Data link tidak valid. URL harus diawali http:// atau https://.');
        }

        $attributes = [
            'external_id' => $externalId,
            'media_type' => $type,
            'provider' => $provider,
        ];
        $values = ['url' => $link];

        if ($id > 0) {
            WatchLink::query()->where('id', $id)->update(array_merge($attributes, $values));
            $flash = 'Link diperbarui.';
        } else {
            WatchLink::create(array_merge($attributes, $values, ['created_by' => $me]));
            $flash = 'Link ditambahkan.';
        }

        return redirect()->to($back)->with('success', $flash);
    }

    private function deleteWatchLink(Request $request): RedirectResponse
    {
        WatchLink::destroy((int) $request->input('watch_id', 0));

        return redirect()->to($this->backUrl($request, 'section=links'))->with('success', 'Link dihapus.');
    }

    private function saveCategory(Request $request): RedirectResponse
    {
        $back = $this->backUrl($request, 'section=categories');
        $id = (int) $request->input('category_id', 0);
        $name = trim((string) $request->input('name', ''));
        $desc = trim((string) $request->input('description', ''));

        if ($name === '' || strlen($name) > 80 || strlen($desc) > 255) {
            return redirect()->to($back)->with('error', 'Nama kategori wajib diisi (maks. 80 karakter), deskripsi maks. 255 karakter.');
        }

        try {
            if ($id > 0) {
                DB::table('forum_categories')->where('id', $id)->update(['name' => $name, 'description' => $desc ?: null]);

                return redirect()->to($back)->with('success', 'Kategori diperbarui.');
            }

            DB::table('forum_categories')->insert(['name' => $name, 'description' => $desc ?: null]);

            return redirect()->to($back)->with('success', 'Kategori ditambahkan.');
        } catch (UniqueConstraintViolationException) {
            return redirect()->to($back)->with('error', 'Nama kategori sudah dipakai.');
        }
    }

    private function deleteCategory(Request $request): RedirectResponse
    {
        $back = $this->backUrl($request, 'section=categories');
        $id = (int) $request->input('category_id', 0);
        $threadCount = (int) DB::table('forum_threads')->where('category_id', $id)->count();

        if ($threadCount > 0) {
            return redirect()->to($back)->with('error', 'Kategori masih punya thread. Pindahkan atau hapus thread-nya dulu.');
        }

        DB::table('forum_categories')->where('id', $id)->delete();

        return redirect()->to($back)->with('success', 'Kategori dihapus.');
    }

    private function isHttpUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && preg_match('#^https?://#i', $url) === 1
            && strlen($url) <= 1000;
    }

    /** URL tujuan setelah aksi: hanya query admin yang bentuknya aman (admin_back di repo asli). */
    private function backUrl(Request $request, string $defaultQuery): string
    {
        $back = (string) $request->input('back', '');
        if (AdminPresenter::backSafe($back)) {
            return url('/admin?'.$back);
        }

        parse_str($defaultQuery, $defaults);
        $section = is_string($defaults['section'] ?? null) ? $defaults['section'] : 'overview';

        return route('admin.section', ['section' => $section]);
    }
}
