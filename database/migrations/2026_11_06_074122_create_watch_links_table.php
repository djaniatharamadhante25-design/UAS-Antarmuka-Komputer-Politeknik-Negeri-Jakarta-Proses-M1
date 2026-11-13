<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('watch_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('external_id');
            $table->enum('media_type', ['anime', 'manga'])->default('anime');
            $table->string('provider', 100);
            $table->string('url', 1000);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // WatchLink memakai $timestamps = false.
            $table->timestamp('created_at')->useCurrent();

            $table->index('provider', 'idx_provider');
            $table->index('external_id');
            $table->index('media_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watch_links');
    }
};
