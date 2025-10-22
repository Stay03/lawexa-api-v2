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
        Schema::create('feedback_images', function (Blueprint $table) {
            $table->id();

            // Feedback this image belongs to
            $table->foreignId('feedback_id')
                ->constrained('feedback')
                ->onDelete('cascade');

            // S3 image path
            $table->string('image_path');

            // Display order
            $table->unsignedTinyInteger('order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('feedback_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_images');
    }
};
