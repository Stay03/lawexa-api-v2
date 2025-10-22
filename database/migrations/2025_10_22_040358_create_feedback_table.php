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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();

            // User who submitted the feedback
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Feedback content
            $table->text('feedback_text');

            // Polymorphic relationship to content (cases, statutes, provisions, divisions, notes)
            // Nullable for page-only feedback
            $table->string('content_type')->nullable();
            $table->unsignedBigInteger('content_id')->nullable();

            // Optional page reference for content-specific feedback
            $table->string('page', 100)->nullable();

            // Feedback status
            $table->enum('status', ['pending', 'under_review', 'resolved'])
                ->default('pending');

            // Resolution tracking
            $table->foreignId('resolved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            // Move to issues tracking
            $table->boolean('moved_to_issues')->default(false);
            $table->foreignId('moved_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('moved_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index(['content_type', 'content_id']);
            $table->index('moved_to_issues');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback');
    }
};
