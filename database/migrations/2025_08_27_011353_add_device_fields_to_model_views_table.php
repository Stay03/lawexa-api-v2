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
            $table->string('device_type', 20)->nullable()->after('ip_timezone');
            $table->string('device_platform', 50)->nullable()->after('device_type');
            $table->string('device_browser', 50)->nullable()->after('device_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            $table->dropColumn(['device_type', 'device_platform', 'device_browser']);
        });
    }
};
