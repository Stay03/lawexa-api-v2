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
        Schema::create('statute_amendments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('original_statute_id')->constrained('statutes')->onDelete('cascade');
            $table->foreignId('amending_statute_id')->constrained('statutes')->onDelete('cascade');
            $table->date('effective_date');
            $table->text('amendment_description')->nullable();
            $table->timestamps();
            
            $table->unique(['original_statute_id', 'amending_statute_id']);
            $table->index(['effective_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statute_amendments');
    }
};
