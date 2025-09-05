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
            // Profile fields - all nullable for backward compatibility
            $table->string('profession', 100)->nullable()->after('device_browser');
            $table->string('country', 100)->nullable()->after('profession');
            $table->string('area_of_expertise', 150)->nullable()->after('country');
            $table->string('university', 200)->nullable()->after('area_of_expertise');
            $table->string('level', 50)->nullable()->after('university'); // undergraduate, graduate, postgraduate, PhD
            $table->unsignedTinyInteger('work_experience')->nullable()->after('level'); // years of experience
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'profession',
                'country',
                'area_of_expertise',
                'university',
                'level',
                'work_experience'
            ]);
        });
    }
};
