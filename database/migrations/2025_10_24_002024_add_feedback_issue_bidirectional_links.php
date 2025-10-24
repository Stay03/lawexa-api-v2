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
        // Add issue_id to feedback table
        Schema::table('feedback', function (Blueprint $table) {
            $table->foreignId('issue_id')
                ->nullable()
                ->after('moved_at')
                ->constrained('issues')
                ->onDelete('set null');

            $table->index('issue_id');
        });

        // Add feedback_id to issues table
        Schema::table('issues', function (Blueprint $table) {
            $table->foreignId('feedback_id')
                ->nullable()
                ->after('user_id')
                ->constrained('feedback')
                ->onDelete('set null');

            $table->index('feedback_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('feedback', function (Blueprint $table) {
            $table->dropForeign(['issue_id']);
            $table->dropIndex(['issue_id']);
            $table->dropColumn('issue_id');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropForeign(['feedback_id']);
            $table->dropIndex(['feedback_id']);
            $table->dropColumn('feedback_id');
        });
    }
};
