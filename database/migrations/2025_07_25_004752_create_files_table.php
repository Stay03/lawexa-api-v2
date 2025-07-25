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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('original_name');
            $table->string('filename');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type');
            $table->bigInteger('size')->unsigned();
            $table->string('category')->default('general');
            $table->text('url')->nullable();
            $table->json('metadata')->nullable();
            
            // Polymorphic relationship columns
            $table->morphs('fileable');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for better performance (morphs() already creates fileable index)
            $table->index('category');
            $table->index('mime_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
