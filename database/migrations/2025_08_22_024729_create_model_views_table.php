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
        Schema::create('model_views', function (Blueprint $table) {
            $table->id();
            $table->morphs('viewable');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 255)->nullable();
            $table->string('ip_address', 45);
            $table->string('user_agent_hash', 64);
            $table->timestamp('viewed_at');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint to prevent duplicate views within cooldown period
            $table->unique(['viewable_type', 'viewable_id', 'user_id'], 'unique_user_view');
            $table->unique(['viewable_type', 'viewable_id', 'session_id', 'ip_address', 'user_agent_hash'], 'unique_guest_view');
            
            // Indexes for performance (morphs() already creates viewable_type, viewable_id index)
            $table->index(['user_id', 'viewed_at']);
            $table->index(['session_id', 'ip_address', 'viewed_at']);
            $table->index('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_views');
    }
};
