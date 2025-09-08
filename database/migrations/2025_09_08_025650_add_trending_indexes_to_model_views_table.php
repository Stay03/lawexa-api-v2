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
            // Composite index for trending queries by content type and date
            $table->index(['viewable_type', 'viewed_at'], 'idx_trending_type_date');
            
            // Composite index for specific item trending analysis
            $table->index(['viewable_type', 'viewable_id', 'viewed_at'], 'idx_trending_item_date');
            
            // Index for user demographic filtering (country-based trending)
            $table->index(['user_id', 'viewed_at'], 'idx_trending_user_date');
            
            // Index for recent views (last 24 hours, 3 days, etc.)
            $table->index(['viewed_at', 'viewable_type'], 'idx_trending_date_type');
            
            // Composite index for user + content type filtering
            $table->index(['user_id', 'viewable_type', 'viewed_at'], 'idx_user_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Drop the trending indexes
            $table->dropIndex('idx_trending_type_date');
            $table->dropIndex('idx_trending_item_date');
            $table->dropIndex('idx_trending_user_date');
            $table->dropIndex('idx_trending_date_type');
            $table->dropIndex('idx_user_type_date');
        });
    }
};
