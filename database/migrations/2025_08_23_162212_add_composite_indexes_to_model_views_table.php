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
        Schema::table('model_views', function (Blueprint $table) {
            // Composite index for authenticated user cooldown queries
            // Optimizes: WHERE viewable_type = ? AND viewable_id = ? AND user_id = ? AND viewed_at >= ?
            $table->index(['viewable_type', 'viewable_id', 'user_id', 'viewed_at'], 'idx_user_views_cooldown');
            
            // Composite index for guest cooldown queries  
            // Optimizes: WHERE viewable_type = ? AND viewable_id = ? AND session_id = ? AND ip_address = ? AND user_agent_hash = ? AND viewed_at >= ?
            // Note: MySQL has a 767 byte limit on index key length, but these string columns should be within limits
            $table->index(['viewable_type', 'viewable_id', 'session_id', 'ip_address', 'user_agent_hash', 'viewed_at'], 'idx_guest_views_cooldown');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Drop the composite indexes
            $table->dropIndex('idx_user_views_cooldown');
            $table->dropIndex('idx_guest_views_cooldown');
        });
    }
};
