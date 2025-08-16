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
        Schema::table('statute_provisions', function (Blueprint $table) {
            $table->longText('provision_text')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statute_provisions', function (Blueprint $table) {
            $table->longText('provision_text')->nullable(false)->change();
        });
    }
};
