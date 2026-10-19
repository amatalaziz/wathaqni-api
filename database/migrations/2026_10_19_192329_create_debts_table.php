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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('creditor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('debtor_id')->nullable()->constrained('users')->restrictOnDelete();
            // The unregistered party is described here; its user ID stays null.
            $table->string('counterparty_name')->nullable();
            $table->string('counterparty_phone', 16)->nullable();
            $table->decimal('amount', 19, 4);
            $table->char('currency', 3);
            $table->date('debt_date');
            $table->date('due_date')->nullable();
            $table->text('description')->nullable();
            $table->enum('visibility', ['private', 'shared'])->default('private');
            $table->enum('agreement_status', ['draft', 'pending', 'accepted', 'rejected', 'cancelled'])->default('draft');
            $table->index(['created_by', 'debt_date']);
            $table->index(['creditor_id', 'agreement_status']);
            $table->index(['debtor_id', 'agreement_status']);
            $table->index('due_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
