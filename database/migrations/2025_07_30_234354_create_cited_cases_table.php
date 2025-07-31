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
        Schema::create('cited_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('court_cases')->onDelete('cascade');
            $table->foreignId('cited_case_id')->constrained('court_cases')->onDelete('cascade');
            $table->timestamps();
            
            // Ensure no duplicate relationships
            $table->unique(['case_id', 'cited_case_id']);
            
            // Add indexes for performance
            $table->index(['case_id']);
            $table->index(['cited_case_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cited_cases');
    }
};
