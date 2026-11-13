<?php

namespace App\Support;

use App\Models\CatalogTitle;

/**
 * Fallback katalog lokal (tabel catalog_titles, struktur sesuai repo asli
 * tetapi isinya opsional). Item diformat agar bentuknya sama dengan
 * response Jikan/AniList yang dipakai view.
 */
final class LocalCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function titles(string $type, string $q = '', int $page = 1, int $perPage = 28): array
    {
        try {
            $offset = max(0, ($page - 1) * $perPage);
            $perPage = max(1, min(100, $perPage));
            $type = $type === 'manga' ? 'manga' : 'anime';

            $query = CatalogTitle::query()->where('media_type', $type);

            if ($q !== '') {
                $search = '%'.$q.'%';
                $query->where(function ($w) use ($search) {
                    $w->where('title', 'like', $search)
                        ->orWhere('title_japanese', 'like', $search)
                        ->orWhere('synopsis', 'like', $search);
                });
            }

            $rows = $query->orderByDesc('popularity')
                ->orderByDesc('score')
                ->skip($offset)
                ->take($perPage)
                ->get();

            $items = [];
            foreach ($rows as $row) {
                $genres = array_filter(array_map('trim', explode(',', (string) $row->genres)));
                $items[] = [
                    'mal_id' => (int) $row->external_id,
                    'anilist_id' => (int) $row->external_id,
                    'title' => $row->title,
                    'title_english' => $row->title,
                    'title_japanese' => $row->title_japanese,
                    'type' => $row->format ?: $type,
                    'format' => $row->format,
                    'score' => $row->score !== null ? (float) $row->score : null,
                    'year' => $row->season_year,
                    'images' => [
                        'jpg' => [
                            'image_url' => $row->image_url,
                            'large_image_url' => $row->image_url,
                        ],
                    ],
                    'coverImage' => ['large' => $row->image_url, 'extraLarge' => $row->image_url],
                    'bannerImage' => $row->banner_url,
                    'description' => $row->synopsis,
                    'genres' => array_map(fn ($g) => ['name' => $g], $genres),
                    'episodes' => $row->episodes,
                    'chapters' => $row->chapters,
                    '_source' => 'Database Lokal',
                ];
            }

            return $items;
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    public static function count(?string $type = null): int
    {
        try {
            $query = CatalogTitle::query();

            if ($type !== null) {
                $query->where('media_type', $type === 'manga' ? 'manga' : 'anime');
            }

            return $query->count();
        } catch (\Throwable $e) {
            report($e);

            return 0;
        }
    }
}
