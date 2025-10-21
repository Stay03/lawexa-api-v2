<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Search tracking fields
            $table->text('search_query')->nullable()->after('is_social_media');
            $table->boolean('is_from_search')->default(false)->after('search_query');
        });

        // Add indexes with database-specific compatibility
        $this->addSearchIndexes();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_model_views_search_viewable');
            $table->dropIndex('idx_model_views_user_search');
            $table->dropIndex('idx_model_views_search_query');

            // Drop columns
            $table->dropColumn(['search_query', 'is_from_search']);
        });
    }

    /**
     * Add search-related indexes with database compatibility
     */
    private function addSearchIndexes(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            // MySQL: Use prefix index for TEXT column (first 255 chars)
            DB::statement('CREATE INDEX idx_model_views_search_query ON model_views (is_from_search, search_query(255))');
        } else {
            // SQLite: Index without prefix
            Schema::table('model_views', function (Blueprint $table) {
                $table->index(['is_from_search', 'search_query'], 'idx_model_views_search_query');
            });
        }

        // These indexes work the same on both databases
        Schema::table('model_views', function (Blueprint $table) {
            // User's search history chronologically
            $table->index(['user_id', 'is_from_search', 'viewed_at'], 'idx_model_views_user_search');

            // Find all searches leading to specific content
            $table->index(['is_from_search', 'viewable_type', 'viewable_id'], 'idx_model_views_search_viewable');
        });
    }
};
