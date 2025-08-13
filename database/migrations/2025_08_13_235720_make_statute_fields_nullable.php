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
        Schema::table('statutes', function (Blueprint $table) {
            $table->string('jurisdiction')->nullable()->change();
            $table->string('country')->nullable()->change();
            $table->longText('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statutes', function (Blueprint $table) {
            $table->string('jurisdiction')->nullable(false)->change();
            $table->string('country')->nullable(false)->change();
            $table->longText('description')->nullable(false)->change();
        });
    }
};
