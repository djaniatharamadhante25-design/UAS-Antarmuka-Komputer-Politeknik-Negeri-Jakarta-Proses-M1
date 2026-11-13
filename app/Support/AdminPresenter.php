<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Visualisasi & paginator dashboard admin (Kelola Data), diporta dari
 * app/admin.php repo asli (admin_date, admin_ago, admin_pager, admin_chart).
 */
final class AdminPresenter
{
    private const PER_PAGE = 15;

    /** Angka query admin yang dibentuk hanya dari karakter aman (param filter hapus/edit/p). */
    private const BACK_PATTERN = '/^section=(overview|users|links|categories|forum)(&[A-Za-z0-9_%.=+\-]*)?$/';

    public static function perPage(): int
    {
        return self::PER_PAGE;
    }

    public static function date(int $timestamp, bool $withYear = true): string
    {
        $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return date('j', $timestamp).' '.$bulan[(int) date('n', $timestamp) - 1].($withYear ? ' '.date('Y', $timestamp) : '');
    }

    public static function ago(?string $ts): string
    {
        if (! $ts) {
            return '—';
        }
        $t = strtotime($ts);
        if ($t === false) {
            return e($ts);
        }
        $d = time() - $t;
        if ($d < 60) {
            return 'baru saja';
        }
        if ($d < 3600) {
            return (int) ($d / 60).' mnt lalu';
        }
        if ($d < 86400) {
            return (int) ($d / 3600).' jam lalu';
        }
        if ($d < 86400 * 7) {
            return (int) ($d / 86400).' hari lalu';
        }

        return self::date($t);
    }

    /** Escape LIKE query agar % dan _ literal, ESCAPE '!' di SQL. */
    public static function like(string $q): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q).'%';
    }

    /**
     * Query string halaman admin yang sedang dibuka (tanpa edit/p),
     * dipakai untuk field hidden "back" supaya aksi kembali ke tampilan sama.
     */
    public static function currentQuery(Request $request): string
    {
        $query = array_filter($request->query(), static fn ($v) => $v !== '' && $v !== null);
        unset($query['edit'], $query['p']);

        return http_build_query($query);
    }

    public static function backField(Request $request): string
    {
        return '<input type="hidden" name="back" value="'.e(self::currentQuery($request)).'">';
    }

    /** Tujuan redirect setelah aksi; query harus lolos validasi aman. */
    public static function backTarget(Request $request, string $default): array
    {
        $back = (string) $request->input('back', '');
        if (self::backSafe($back)) {
            ['section' => $section] = self::parseBack($back);

            return ['r' => 'admin.section', 'p' => ['section' => $section], 'q' => self::queryAfterSection($back)];
        }

        return ['r' => $default, 'p' => [], 'q' => $default === 'admin.section' ? ['section' => 'overview'] : []];
    }

    public static function backSafe(string $back): bool
    {
        return preg_match(self::BACK_PATTERN, $back) === 1;
    }

    private static function parseBack(string $back): array
    {
        parse_str($back, $parsed);

        return $parsed;
    }

    private static function queryAfterSection(string $back): array
    {
        $parsed = self::parseBack($back);
        unset($parsed['section']);

        return $parsed;
    }

    /**
     * @param  array  $pg  allias hasil paginate: total, page, pages
     * @param  array  $params  filter aktif (tanpa p)
     */
    public static function pager(array $pg, array $params): string
    {
        $pages = (int) ($pg['pages'] ?? 1);
        if ($pages <= 1) {
            return '';
        }

        $cur = (int) ($pg['page'] ?? 1);
        $last = $pages;
        $nums = array_values(array_unique(array_filter(
            [1, $cur - 1, $cur, $cur + 1, $last],
            static fn ($n) => $n >= 1 && $n <= $last
        )));
        sort($nums);

        $html = '<nav class="adm-pager" aria-label="Halaman">';
        $link = static function (int $n, string $label, bool $disabled = false, bool $active = false) use ($params): string {
            if ($disabled) {
                return '<span class="adm-page is-disabled">'.$label.'</span>';
            }
            $href = e(route('admin.section', $params + ['p' => $n]));
            $activeAttr = $active ? ' aria-current="page"' : '';

            return '<a class="adm-page'.($active ? ' is-active' : '').'" href="'.$href.'"'.$activeAttr.'>'.$label.'</a>';
        };
        $html .= $link($cur - 1, '&lsaquo; Sebelumnya', $cur <= 1);
        $prev = 0;
        foreach ($nums as $n) {
            if ($prev && $n - $prev > 1) {
                $html .= '<span class="adm-gap">…</span>';
            }
            $html .= $link($n, (string) $n, false, $n === $cur);
            $prev = $n;
        }
        $html .= $link($cur + 1, 'Berikutnya &rsaquo;', $cur >= $last);

        return $html.'</nav>';
    }

    /**
     * Grafik batang berpasangan (SVG, dibuat di server). Warna lewat CSS supaya ikut tema.
     *
     * @param  list<string>  $labels  label sumbu x (Y-m-d)
     * @param  list<int>  $a
     * @param  list<int>  $b
     */
    public static function chart(array $labels, array $a, array $b, string $nameA, string $nameB): string
    {
        $w = 640;
        $h = 230;
        $pl = 30;
        $pr = 6;
        $pt = 10;
        $pb = 28;
        $plotW = $w - $pl - $pr;
        $plotH = $h - $pt - $pb;
        $max = max([4, ...$a, ...$b]);
        $max = (int) (ceil($max / 4) * 4);
        $n = count($labels);
        $slot = $n > 0 ? $plotW / $n : $plotW;
        $barW = min(13, $slot * 0.34);

        $svg = '<svg class="adm-chart" viewBox="0 0 '.$w.' '.$h.'" role="img" aria-label="Grafik harian: '.e($nameA).' dan '.e($nameB).' selama '.$n.' hari terakhir">';
        for ($i = 0; $i <= 4; $i++) {
            $y = $pt + $plotH - ($plotH * $i / 4);
            $svg .= '<line class="c-grid" x1="'.$pl.'" x2="'.($w - $pr).'" y1="'.round($y, 1).'" y2="'.round($y, 1).'"/>';
            $svg .= '<text class="c-txt" x="'.($pl - 6).'" y="'.round($y + 3.5, 1).'" text-anchor="end">'.(int) ($max * $i / 4).'</text>';
        }
        foreach ($labels as $i => $day) {
            $cx = $pl + $slot * $i + $slot / 2;
            $ts = strtotime($day);
            $tip = self::date((int) $ts, false);
            $series = [[$a[$i] ?? 0, 'c-a', -$barW - 1, $nameA], [$b[$i] ?? 0, 'c-b', 1, $nameB]];
            foreach ($series as [$v, $cls, $dx, $nm]) {
                $bh = $v > 0 ? max(2.5, $plotH * $v / $max) : 0;
                if ($bh > 0) {
                    $svg .= '<rect class="'.$cls.'" x="'.round($cx + $dx, 1).'" y="'.round($pt + $plotH - $bh, 1).'" width="'.round($barW, 1).'" height="'.round($bh, 1).'" rx="2"><title>'.e($tip.' · '.$v.' '.$nm).'</title></rect>';
                }
            }
            if ($i % 2 === 1 || $i === $n - 1) {
                $svg .= '<text class="c-txt" x="'.round($cx, 1).'" y="'.($h - 8).'" text-anchor="middle">'.date('j', (int) $ts).'</text>';
            }
        }

        return $svg.'</svg>';
    }
}
