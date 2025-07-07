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
        Schema::table('oauth_states', function (Blueprint $table) {
            $table->string('token', 500)->nullable()->change();
            $table->json('user_data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_states', function (Blueprint $table) {
            $table->string('token', 500)->nullable(false)->change();
            $table->json('user_data')->nullable(false)->change();
        });
    }
};
