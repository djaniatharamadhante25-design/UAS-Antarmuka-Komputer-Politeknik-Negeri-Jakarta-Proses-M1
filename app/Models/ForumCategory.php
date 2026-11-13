<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    public const DEFAULTS = [
        'General' => 'Diskusi umum seputar anime, manga, dan komunitas.',
        'Anime' => 'Diskusi judul, episode, karakter, dan teori anime.',
        'Manga' => 'Diskusi manga, chapter, author, dan teori.',
        'Release Discussion' => 'Diskusi rilisan dan episode/chapter terbaru.',
        'Recommendations' => 'Rekomendasi anime atau manga.',
        'Site Feedback' => 'Saran dan laporan bug untuk Otaku Track.',
    ];

    /*
     * Tabel legacy ini tidak punya kolom updated_at.
     */
    public $timestamps = false;

    protected $fillable = ['name', 'description'];

    public function threads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }
}
