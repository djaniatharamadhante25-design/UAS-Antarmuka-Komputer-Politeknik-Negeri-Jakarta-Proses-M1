<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('forum_threads')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // ForumVote memakai $timestamps = false.
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['thread_id', 'user_id'], 'uq_vote');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_votes');
    }
};
