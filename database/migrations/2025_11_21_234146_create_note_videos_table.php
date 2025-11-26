<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('note_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->cascadeOnDelete();
            $table->string('video_url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->enum('platform', ['youtube', 'dailymotion', 'other'])->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['note_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('note_videos');
    }
};
