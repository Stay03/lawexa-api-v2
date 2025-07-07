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
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('is_error')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oauth_states', function (Blueprint $table) {
            $table->dropColumn(['error_code', 'error_message', 'is_error']);
        });
    }
};
