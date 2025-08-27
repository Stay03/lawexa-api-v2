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
            $table->text('user_agent')->nullable()->after('user_agent_hash');
            $table->string('ip_country', 100)->nullable()->after('user_agent');
            $table->string('ip_country_code', 10)->nullable()->after('ip_country');
            $table->string('ip_continent', 100)->nullable()->after('ip_country_code');
            $table->string('ip_continent_code', 10)->nullable()->after('ip_continent');
            $table->string('ip_region', 100)->nullable()->after('ip_continent_code');
            $table->string('ip_city', 100)->nullable()->after('ip_region');
            $table->string('ip_timezone', 50)->nullable()->after('ip_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('model_views', function (Blueprint $table) {
            $table->dropColumn([
                'user_agent',
                'ip_country',
                'ip_country_code',
                'ip_continent',
                'ip_continent_code',
                'ip_region',
                'ip_city',
                'ip_timezone'
            ]);
        });
    }
};
