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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plan_code')->unique();
            $table->text('description')->nullable();
            $table->integer('amount');
            $table->string('currency', 3)->default('NGN');
            $table->enum('interval', ['hourly', 'daily', 'weekly', 'monthly', 'quarterly', 'biannually', 'annually']);
            $table->integer('invoice_limit')->default(0);
            $table->boolean('send_invoices')->default(true);
            $table->boolean('send_sms')->default(true);
            $table->boolean('hosted_page')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
