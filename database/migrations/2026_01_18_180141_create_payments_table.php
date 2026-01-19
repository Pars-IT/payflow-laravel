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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('gateway')->default('ideal');
            $table->string('provider')->nullable();
            $table->string('provider_payment_id')->nullable()->index();
            $table->text('provider_checkout_url')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'success', 'failed'])->index();
            $table->string('failure_reason')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
