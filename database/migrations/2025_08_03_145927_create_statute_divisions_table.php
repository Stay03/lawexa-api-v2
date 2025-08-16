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
        Schema::create('statute_divisions', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
            $table->foreignId('parent_division_id')->nullable()->constrained('statute_divisions')->onDelete('cascade');
            $table->enum('division_type', ['part', 'chapter', 'article', 'title', 'book', 'division', 'section', 'subsection', 'schedule']);
            $table->string('division_number');
            $table->string('division_title');
            $table->string('division_subtitle')->nullable();
            $table->longText('content')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('level')->default(1);
            $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
            $table->date('effective_date')->nullable();
            $table->timestamps();
            
            // Composite unique constraint for slug within statute
            $table->unique(['statute_id', 'slug']);
            $table->index(['statute_id', 'parent_division_id']);
            $table->index(['division_type', 'status']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statute_divisions');
    }
};
