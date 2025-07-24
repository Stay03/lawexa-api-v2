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
        Schema::create('court_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->text('report')->nullable();
            $table->string('course')->nullable();
            $table->string('topic')->nullable();
            $table->string('tag')->nullable();
            $table->text('principles')->nullable();
            $table->string('level')->nullable();
            $table->string('slug')->unique();
            $table->string('court')->nullable();
            $table->date('date')->nullable();
            $table->string('country')->nullable();
            $table->string('citation')->nullable();
            $table->text('judges')->nullable();
            $table->text('judicial_precedent')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['country', 'court']);
            $table->index(['topic', 'level']);
            $table->index(['date']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('court_cases');
    }
};
