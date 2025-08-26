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
            // Drop the permanent unique constraints that prevent time-based cooldowns
            $table->dropUnique('unique_user_view');
            $table->dropUnique('unique_guest_view');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            // Restore the unique constraints
            $table->unique(['viewable_type', 'viewable_id', 'user_id'], 'unique_user_view');
            $table->unique(['viewable_type', 'viewable_id', 'session_id', 'ip_address', 'user_agent_hash'], 'unique_guest_view');
        });
    }
};
