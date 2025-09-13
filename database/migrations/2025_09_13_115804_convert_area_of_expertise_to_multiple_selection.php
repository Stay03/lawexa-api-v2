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
        // First, migrate existing data
        \DB::statement("
            UPDATE users
            SET area_of_expertise = JSON_ARRAY(area_of_expertise)
            WHERE area_of_expertise IS NOT NULL
            AND area_of_expertise != ''
            AND JSON_VALID(area_of_expertise) = 0
        ");

        // Then alter the column to JSON type
        Schema::table('users', function (Blueprint $table) {
            $table->json('area_of_expertise')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, convert JSON arrays back to single strings (take first element)
        \DB::statement("
            UPDATE users
            SET area_of_expertise = JSON_UNQUOTE(JSON_EXTRACT(area_of_expertise, '$[0]'))
            WHERE area_of_expertise IS NOT NULL
            AND JSON_VALID(area_of_expertise) = 1
        ");

        // Then alter the column back to string type
        Schema::table('users', function (Blueprint $table) {
            $table->string('area_of_expertise', 150)->nullable()->change();
        });
    }
};
