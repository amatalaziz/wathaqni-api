<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Never invent financial data for legacy placeholder rows.
        if (DB::table('debts')->exists()) {
            throw new RuntimeException('Backfill existing debt placeholders before adding required debt details.');
        }

        Schema::table('debts', function (Blueprint $table) {
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
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['creditor_id']);
            $table->dropForeign(['debtor_id']);
            $table->dropIndex(['created_by', 'debt_date']);
            $table->dropIndex(['creditor_id', 'agreement_status']);
            $table->dropIndex(['debtor_id', 'agreement_status']);
            $table->dropIndex(['due_date']);
            $table->dropColumn(['created_by', 'creditor_id', 'debtor_id', 'counterparty_name', 'counterparty_phone', 'amount', 'currency', 'debt_date', 'due_date', 'description', 'visibility', 'agreement_status']);
        });
    }
};
