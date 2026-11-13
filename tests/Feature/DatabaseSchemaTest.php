<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabel_yang_dipakai_model_tersedia(): void
    {
        $tables = [
            'users',
            'catalog_titles',
            'media_lists',
            'forum_categories',
            'forum_threads',
            'forum_replies',
            'forum_votes',
            'watch_links',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel {$table} tidak ditemukan.");
        }
    }

    public function test_seeder_membuat_admin_dan_enam_kategori_forum(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
            'role' => 'admin',
        ]);

        $this->assertDatabaseCount('forum_categories', 6);
    }
}
