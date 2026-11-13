<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Katalog fallback lokal. Diisi opsional lewat seeder/importer;
     * dipakai LocalCatalog saat Jikan & AniList tidak bisa dihubungi.
     */
    public function up(): void
    {
        Schema::create('catalog_titles', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id');
            $table->enum('media_type', ['anime', 'manga']);
            $table->string('title');
            $table->string('title_japanese')->nullable();
            $table->string('format', 50)->nullable();
            $table->string('status', 50)->nullable();
            $table->decimal('score', 3, 1)->nullable();
            $table->unsignedInteger('season_year')->nullable();
            $table->unsignedInteger('episodes')->nullable();
            $table->unsignedInteger('chapters')->nullable();
            $table->string('genres')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('banner_url', 500)->nullable();
            $table->text('synopsis')->nullable();
            $table->unsignedInteger('popularity')->default(0);
            $table->timestamps();

            $table->unique(['media_type', 'external_id'], 'uq_catalog_media');
            $table->index(['media_type', 'score'], 'idx_type_score');
            $table->index(['media_type', 'popularity'], 'idx_type_popularity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_titles');
    }
};
