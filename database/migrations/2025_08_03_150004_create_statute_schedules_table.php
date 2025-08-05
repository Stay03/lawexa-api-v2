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
        Schema::create('statute_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
            $table->string('schedule_number');
            $table->string('schedule_title');
            $table->longText('content');
            $table->string('schedule_type')->nullable();
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
            $table->date('effective_date')->nullable();
            $table->timestamps();
            
            // Composite unique constraint for slug within statute
            $table->unique(['statute_id', 'slug']);
            $table->index(['statute_id', 'schedule_number']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statute_schedules');
    }
};
