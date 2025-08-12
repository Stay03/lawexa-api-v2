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
            $table->string('range')->nullable()->after('interpretation_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statute_provisions', function (Blueprint $table) {
            $table->dropColumn('range');
        });
    }
};
