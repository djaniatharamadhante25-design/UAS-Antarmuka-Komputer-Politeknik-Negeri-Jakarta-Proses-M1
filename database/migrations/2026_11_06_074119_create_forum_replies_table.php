<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_hidden')->default(false);
            // ForumReply memakai $timestamps = false.
            $table->timestamp('created_at')->useCurrent();

            $table->index('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
    }
};
