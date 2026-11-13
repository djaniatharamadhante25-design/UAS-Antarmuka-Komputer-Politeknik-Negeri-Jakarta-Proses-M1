<?php

namespace App\Support;

/*
 * Helper tampilan global yang diporting dari app/helpers.php repo asli.
 * Dipanggil dari Blade sebagai OtakuHelper::... supaya tidak menambah
 * fungsi global yang jarang dipakai.
 */
final class OtakuHelper
{
    public static function imageUrl(?string $url): string
    {
        return $url !== '' && $url !== null
            ? $url
            : 'https://placehold.co/300x430/111827/ffffff?text=No+Cover';
    }

    public static function formatStatus(?string $status): string
    {
        return ucwords(str_replace('_', ' ', $status ?? ''));
    }

    public static function statusLabel(string $status): string
    {
        return [
            'watching' => 'Sedang ditonton',
            'completed' => 'Selesai',
            'on_hold' => 'Ditunda',
            'dropped' => 'Dihentikan',
            'plan_to_watch' => 'Rencana nonton',
            'reading' => 'Sedang dibaca',
            'plan_to_read' => 'Rencana baca',
        ][$status] ?? self::formatStatus($status);
    }

    public static function safeDescription(?string $description): string
    {
        if (! $description) {
            return 'Belum ada synopsis yang tersedia.';
        }

        return trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $description)));
    }

    /**
     * URL halaman detail untuk sebuah judul anime/manga.
     * MAL ID memakai route /detail, AniList-only memakai parameter identik repo asli.
     */
    public static function mediaDetailUrl(string $type, int $malId = 0, int $aniId = 0): string
    {
        $type = $type === 'manga' ? 'manga' : 'anime';

        if ($malId > 0 || $aniId > 0) {
            return route('detail', array_filter([
                'type' => $type,
                'mal_id' => $malId > 0 ? $malId : null,
                'anilist_id' => $aniId > 0 ? $aniId : null,
            ]));
        }

        return route('search', ['type' => $type]);
    }

    public static function mediaLink(string $type, array $item): string
    {
        $type = $type === 'manga' ? 'manga' : 'anime';
        // Penting: jangan satukan dua namespace dalam satu id. Simpan keduanya.
        $malId = (int) ($item['mal_id'] ?? $item['idMal'] ?? 0);
        $aniId = (int) ($item['anilist_id'] ?? $item['id'] ?? 0);

        return self::mediaDetailUrl($type, $malId, $aniId);
    }

    /**
     * Kode karakter untuk enum status media_lists (dipakai controller & validasi).
     *
     * @return list<string>
     */
    public static function listStatuses(): array
    {
        return ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch', 'reading', 'plan_to_read'];
    }
}
