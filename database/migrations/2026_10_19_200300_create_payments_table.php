<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            // Caller reuses this UUID for retries of the same payment.
            $table->uuid('idempotency_key')->unique();
            $table->decimal('amount', 19, 4);
            // Currency is inherited from the debt.
            $table->string('payment_method', 32);
            $table->timestamp('paid_at');
            $table->enum('confirmation_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->unique(['id', 'debt_id']);
            $table->index(['debt_id', 'confirmation_status', 'paid_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
