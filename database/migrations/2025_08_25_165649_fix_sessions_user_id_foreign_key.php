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
        // Clean up any orphaned sessions before adding foreign key constraint
        // Cross-database compatible cleanup queries
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL supports DELETE with JOIN
            DB::statement('DELETE s FROM sessions s LEFT JOIN users u ON s.user_id = u.id WHERE s.user_id IS NOT NULL AND u.id IS NULL');
        } else {
            // SQLite, PostgreSQL, and other databases - use subquery approach
            DB::statement('DELETE FROM sessions WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)');
        }
        
        Schema::table('sessions', function (Blueprint $table) {
            // Add foreign key constraint with cascade delete
            // This Laravel method is cross-database compatible
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);
        });
    }
};
