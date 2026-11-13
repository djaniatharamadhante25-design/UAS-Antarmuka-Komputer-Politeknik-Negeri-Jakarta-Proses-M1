<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('forum_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('body');
            $table->unsignedInteger('external_id')->nullable();
            $table->enum('media_type', ['anime', 'manga'])->nullable();
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();

            $table->index('created_at', 'idx_thread_created');
            $table->index('external_id');
            $table->index('is_locked');
            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};
