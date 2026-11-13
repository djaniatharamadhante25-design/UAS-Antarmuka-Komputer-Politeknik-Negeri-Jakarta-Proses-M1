<?php

namespace App\Models;

use Illuminate\Database\Eloquent\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class WatchLink extends Model
{
    public const MEDIA_TYPES = ['anime', 'manga'];

    /*
     * Tabel legacy ini tidak punya kolom updated_at.
     */
    public $timestamps = false;

    protected $fillable = [
        'external_id', 'media_type', 'provider', 'url', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'external_id' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
