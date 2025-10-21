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
        Schema::create('content_requests', function (Blueprint $table) {
            $table->id();

            // User who made the request
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Request type: case, statute, provision, division
            $table->enum('type', ['case', 'statute', 'provision', 'division'])
                ->default('case');

            // What the user is requesting
            $table->string('title', 500);
            $table->text('additional_notes')->nullable();

            // Polymorphic relationship to created content
            // e.g., created_content_type = 'App\Models\CourtCase'
            //       created_content_id = 5109
            $table->string('created_content_type')->nullable();
            $table->unsignedBigInteger('created_content_id')->nullable();

            // For provisions/divisions: which statute they belong to
            $table->foreignId('statute_id')
                ->nullable()
                ->constrained('statutes')
                ->onDelete('cascade');

            // For nested divisions (Division under Division)
            $table->foreignId('parent_division_id')
                ->nullable()
                ->constrained('statute_divisions')
                ->onDelete('cascade');

            // For nested provisions (Provision under Provision)
            $table->foreignId('parent_provision_id')
                ->nullable()
                ->constrained('statute_provisions')
                ->onDelete('cascade');

            // Request status
            $table->enum('status', ['pending', 'in_progress', 'fulfilled', 'rejected'])
                ->default('pending');

            // Fulfillment tracking
            $table->foreignId('fulfilled_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('fulfilled_at')->nullable();

            // Rejection tracking
            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('status');
            $table->index('type');
            $table->index(['created_content_type', 'created_content_id']);
            $table->index('statute_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_requests');
    }
};
