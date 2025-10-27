<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add order_index column to statute_divisions and statute_provisions tables
     * to enable hash-first lazy loading and sequential content navigation.
     *
     * The order_index represents the sequential reading order of all content
     * within a statute (both divisions and provisions unified).
     */
    public function up(): void
    {
        // Add order_index to statute_divisions
        Schema::table('statute_divisions', function (Blueprint $table) {
            $table->integer('order_index')->nullable()->after('level');

            // Composite index for efficient range queries (WHERE statute_id = ? AND order_index < ?)
            $table->index(['statute_id', 'order_index', 'status'], 'idx_divisions_order');

            // Index for slug lookups (WHERE statute_id = ? AND slug = ?)
            $table->index(['statute_id', 'slug'], 'idx_divisions_slug');
        });

        // Add order_index to statute_provisions
        Schema::table('statute_provisions', function (Blueprint $table) {
            $table->integer('order_index')->nullable()->after('level');

            // Composite index for efficient range queries
            $table->index(['statute_id', 'order_index', 'status'], 'idx_provisions_order');

            // Index for slug lookups
            $table->index(['statute_id', 'slug'], 'idx_provisions_slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statute_divisions', function (Blueprint $table) {
            $table->dropIndex('idx_divisions_order');
            $table->dropIndex('idx_divisions_slug');
            $table->dropColumn('order_index');
        });

        Schema::table('statute_provisions', function (Blueprint $table) {
            $table->dropIndex('idx_provisions_order');
            $table->dropIndex('idx_provisions_slug');
            $table->dropColumn('order_index');
        });
    }
};
