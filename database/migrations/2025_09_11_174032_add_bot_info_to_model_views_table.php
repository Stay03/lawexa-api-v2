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
            // Bot detection fields
            $table->boolean('is_bot')->nullable()->after('viewed_at');
            $table->string('bot_name', 100)->nullable()->after('is_bot');
            $table->boolean('is_search_engine')->nullable()->after('bot_name');
            $table->boolean('is_social_media')->nullable()->after('is_search_engine');
            
            // Indexes for analytics and filtering
            $table->index('is_bot');
            $table->index(['is_bot', 'viewed_at']);
            $table->index(['bot_name', 'viewed_at']);
            $table->index(['is_search_engine', 'viewed_at']);
            $table->index(['is_social_media', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['model_views_is_social_media_viewed_at_index']);
            $table->dropIndex(['model_views_is_search_engine_viewed_at_index']);
            $table->dropIndex(['model_views_bot_name_viewed_at_index']);
            $table->dropIndex(['model_views_is_bot_viewed_at_index']);
            $table->dropIndex(['model_views_is_bot_index']);
            
            // Drop columns
            $table->dropColumn(['is_bot', 'bot_name', 'is_search_engine', 'is_social_media']);
        });
    }
};
