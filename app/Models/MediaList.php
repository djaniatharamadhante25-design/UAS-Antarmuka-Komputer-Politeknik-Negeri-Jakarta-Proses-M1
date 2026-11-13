<?php

namespace App\Models;

use Illuminate\Database\Eloquent\BelongsTo;
use Illuminate\Database\Eloquent\Model;

/*
 * Kolom tabel mengikuti struktur legacy repo asli:
 * media_type => enum('anime','manga'), status => beberapa enum progres.
 */
class MediaList extends Model
{
    public const STATUS_OPTIONS = [
        'watching', 'completed', 'on_hold', 'dropped',
        'plan_to_watch', 'reading', 'plan_to_read',
    ];

    public const MEDIA_TYPES = ['anime', 'manga'];

    protected $fillable = [
        'user_id', 'media_type', 'external_id', 'title', 'image_url',
        'status', 'score', 'progress', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'progress' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return [
            'watching' => 'Sedang ditonton',
            'completed' => 'Selesai',
            'on_hold' => 'Ditunda',
            'dropped' => 'Dihentikan',
            'plan_to_watch' => 'Rencana nonton',
            'reading' => 'Sedang dibaca',
            'plan_to_read' => 'Rencana baca',
        ][$this->status] ?? ucwords(str_replace('_', ' ', (string) $this->status));
    }

    public function formattedStatus(): string
    {
        return $this->statusLabel();
    }
}
