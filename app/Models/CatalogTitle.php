<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Katalog lokal (fallback). Diinstalasi ini tabel hanya struktur — isian 1.000 judul
 * opsional dan dapat dikerjakan lewat artisan command ketika dibutuhkan.
 */
class CatalogTitle extends Model
{
    public const MEDIA_TYPES = ['anime', 'manga'];

    protected $fillable = [
        'external_id', 'media_type', 'title', 'title_japanese', 'format', 'status',
        'score', 'season_year', 'episodes', 'chapters', 'genres',
        'image_url', 'banner_url', 'synopsis', 'popularity',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'season_year' => 'integer',
            'episodes' => 'integer',
            'chapters' => 'integer',
            'popularity' => 'integer',
            'external_id' => 'integer',
        ];
    }
}
