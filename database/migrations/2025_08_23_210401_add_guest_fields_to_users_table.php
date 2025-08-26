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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('guest_expires_at')->nullable()->after('remember_token');
            $table->timestamp('last_activity_at')->nullable()->after('guest_expires_at');
            
            // Index for cleanup job performance
            $table->index(['role', 'guest_expires_at', 'last_activity_at'], 'guest_cleanup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('guest_cleanup_index');
            $table->dropColumn(['guest_expires_at', 'last_activity_at']);
        });
    }
};
