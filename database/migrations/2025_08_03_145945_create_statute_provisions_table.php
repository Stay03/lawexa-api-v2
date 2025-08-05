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
        Schema::create('statute_provisions', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->foreignId('statute_id')->constrained('statutes')->onDelete('cascade');
            $table->foreignId('division_id')->nullable()->constrained('statute_divisions')->onDelete('cascade');
            $table->foreignId('parent_provision_id')->nullable()->constrained('statute_provisions')->onDelete('cascade');
            $table->enum('provision_type', ['section', 'subsection', 'paragraph', 'subparagraph', 'clause', 'subclause', 'item']);
            $table->string('provision_number');
            $table->string('provision_title')->nullable();
            $table->longText('provision_text');
            $table->text('marginal_note')->nullable();
            $table->text('interpretation_note')->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('level')->default(1);
            $table->enum('status', ['active', 'repealed', 'amended'])->default('active');
            $table->date('effective_date')->nullable();
            $table->timestamps();
            
            // Composite unique constraint for slug within statute
            $table->unique(['statute_id', 'slug']);
            $table->index(['statute_id', 'division_id']);
            $table->index(['provision_type', 'status']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statute_provisions');
    }
};
