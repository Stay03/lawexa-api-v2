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
        Schema::table('issues', function (Blueprint $table) {
            $table->foreignId('resolved_by')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->onDelete('set null');

            $table->index('resolved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropIndex(['resolved_by']);
            $table->dropColumn('resolved_by');
        });
    }
};
