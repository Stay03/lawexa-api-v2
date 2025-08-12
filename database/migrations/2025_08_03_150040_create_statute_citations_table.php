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
        Schema::create('statute_citations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citing_statute_id')->constrained('statutes')->onDelete('cascade');
            $table->foreignId('cited_statute_id')->constrained('statutes')->onDelete('cascade');
            $table->string('citation_context')->nullable();
            $table->timestamps();
            
            $table->unique(['citing_statute_id', 'cited_statute_id'], 'stat_cite_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statute_citations');
    }
};
