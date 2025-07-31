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
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->enum('type', ['bug', 'feature_request', 'improvement', 'other'])->default('bug');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'duplicate'])->default('open');
            $table->enum('area', ['frontend', 'backend', 'both'])->nullable();
            $table->string('category', 100)->nullable();
            $table->json('browser_info')->nullable();
            $table->json('environment_info')->nullable();
            $table->text('steps_to_reproduce')->nullable();
            $table->text('expected_behavior')->nullable();
            $table->text('actual_behavior')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();
            $table->text('ai_analysis')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('severity');
            $table->index('area');
            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
