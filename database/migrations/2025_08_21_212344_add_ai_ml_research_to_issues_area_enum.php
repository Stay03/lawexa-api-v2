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
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL-specific approach: Use raw SQL to modify enum
            DB::statement("ALTER TABLE issues MODIFY COLUMN area ENUM('frontend', 'backend', 'both', 'ai_ml', 'research') NULL");
        } else {
            // SQLite and other databases
            Schema::table('issues', function (Blueprint $table) {
                $table->enum('area', ['frontend', 'backend', 'both', 'ai_ml', 'research'])->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'mysql') {
            // MySQL-specific approach: Use raw SQL to modify enum
            DB::statement("ALTER TABLE issues MODIFY COLUMN area ENUM('frontend', 'backend', 'both') NULL");
        } else {
            // SQLite and other databases
            Schema::table('issues', function (Blueprint $table) {
                $table->enum('area', ['frontend', 'backend', 'both'])->nullable()->change();
            });
        }
    }
};
