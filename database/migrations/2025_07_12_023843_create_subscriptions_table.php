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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->string('subscription_code')->unique();
            $table->string('email_token');
            $table->string('status')->default('active');
            $table->integer('quantity')->default(1);
            $table->integer('amount');
            $table->string('currency', 3)->default('NGN');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('next_payment_date')->nullable();
            $table->string('cron_expression')->nullable();
            $table->string('authorization_code')->nullable();
            $table->json('authorization_data')->nullable();
            $table->integer('invoice_limit')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
