<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            // Composite foreign keys prevent allocations across different debts.
            $table->foreignId('debt_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id');
            $table->foreignId('debt_schedule_id');
            $table->decimal('amount', 19, 4);
            $table->unique(['payment_id', 'debt_schedule_id']);
            $table->foreign(['payment_id', 'debt_id'])->references(['id', 'debt_id'])->on('payments')->restrictOnDelete();
            $table->foreign(['debt_schedule_id', 'debt_id'])->references(['id', 'debt_id'])->on('debt_schedules')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
