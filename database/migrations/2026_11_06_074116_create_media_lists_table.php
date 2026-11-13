<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Watchlist/reading list pribadi (MediaList model).
     */
    public function up(): void
    {
        Schema::create('media_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('media_type', ['anime', 'manga']);
            $table->unsignedInteger('external_id');
            $table->string('title');
            $table->string('image_url', 500)->nullable();
            $table->enum('status', [
                'watching', 'completed', 'on_hold', 'dropped',
                'plan_to_watch', 'reading', 'plan_to_read',
            ]);
            $table->decimal('score', 3, 1)->nullable();
            $table->unsignedInteger('progress')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'media_type', 'external_id'], 'uq_user_media');
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['user_id', 'updated_at'], 'idx_user_updated');
            $table->index('external_id', 'idx_external');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_lists');
    }
};
