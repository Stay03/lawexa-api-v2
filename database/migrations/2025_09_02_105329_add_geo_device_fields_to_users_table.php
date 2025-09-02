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
            // Registration network information
            $table->string('registration_ip_address', 45)->nullable()->after('password');
            $table->text('registration_user_agent')->nullable()->after('registration_ip_address');
            
            // Registration geolocation data
            $table->string('ip_country', 100)->nullable()->after('registration_user_agent');
            $table->string('ip_country_code', 2)->nullable()->after('ip_country');
            $table->string('ip_continent', 100)->nullable()->after('ip_country_code');
            $table->string('ip_continent_code', 2)->nullable()->after('ip_continent');
            $table->string('ip_region', 100)->nullable()->after('ip_continent_code');
            $table->string('ip_city', 100)->nullable()->after('ip_region');
            $table->string('ip_timezone', 50)->nullable()->after('ip_city');
            
            // Registration device information
            $table->string('device_type', 50)->nullable()->after('ip_timezone');
            $table->string('device_platform', 50)->nullable()->after('device_type');
            $table->string('device_browser', 50)->nullable()->after('device_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'registration_ip_address',
                'registration_user_agent',
                'ip_country',
                'ip_country_code',
                'ip_continent',
                'ip_continent_code',
                'ip_region',
                'ip_city',
                'ip_timezone',
                'device_type',
                'device_platform',
                'device_browser'
            ]);
        });
    }
};
