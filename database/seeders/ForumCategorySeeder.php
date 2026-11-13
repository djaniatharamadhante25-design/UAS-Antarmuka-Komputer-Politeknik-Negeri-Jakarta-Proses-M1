<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    /**
     * Kategori forum default Otaku Track (sama seperti schema.sql repo asli).
     * Dipasang hanya bila belum ada agar tidak menduplikasi data legacy.
     */
    public function run(): void
    {
        $categories = [
            'General' => 'Diskusi umum seputar anime, manga, dan komunitas.',
            'Anime' => 'Diskusi judul, episode, karakter, dan teori anime.',
            'Manga' => 'Diskusi manga, chapter, author, dan teori.',
            'Release Discussion' => 'Diskusi rilisan dan episode/chapter terbaru.',
            'Recommendations' => 'Rekomendasi anime atau manga.',
            'Site Feedback' => 'Saran dan laporan bug untuk Otaku Track.',
        ];

        foreach ($categories as $name => $description) {
            ForumCategory::firstOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }
    }
}
