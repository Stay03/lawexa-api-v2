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
        Schema::create('statutes', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('short_title')->nullable();
            $table->year('year_enacted')->nullable();
            $table->date('commencement_date')->nullable();
            $table->enum('status', ['active', 'repealed', 'amended', 'suspended'])->default('active');
            $table->date('repealed_date')->nullable();
            $table->foreignId('repealing_statute_id')->nullable()->constrained('statutes')->onDelete('set null');
            $table->foreignId('parent_statute_id')->nullable()->constrained('statutes')->onDelete('set null');
            $table->string('jurisdiction');
            $table->string('country');
            $table->string('state')->nullable();
            $table->string('local_government')->nullable();
            $table->string('citation_format')->nullable();
            $table->string('sector')->nullable();
            $table->json('tags')->nullable();
            $table->longText('description');
            $table->string('range')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Strategic indexes
            $table->index(['status', 'jurisdiction']);
            $table->index(['country', 'state']);
            $table->index(['sector']);
            $table->index(['year_enacted']);
            $table->index(['parent_statute_id']);
            $table->index(['created_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statutes');
    }
};
