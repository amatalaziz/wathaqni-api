<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Requires MySQL 8.0.16+ or MariaDB 10.2.1+ (enforced CHECK constraints).
    private array $constraints = [
        'users' => [
            'users_phone_format' => "phone IS NULL OR phone REGEXP '^[+][1-9][0-9]{1,14}$'",
        ],
        'debts' => [
            'debts_amount_positive' => 'amount > 0',
            'debts_dates_valid' => 'due_date IS NULL OR due_date >= debt_date',
            'debts_parties_distinct' => 'creditor_id IS NULL OR debtor_id IS NULL OR creditor_id <> debtor_id',
            'debts_creator_party' => '(creditor_id IS NOT NULL AND created_by = creditor_id) OR (debtor_id IS NOT NULL AND created_by = debtor_id)',
            'debts_accepted_parties' => "agreement_status <> 'accepted' OR (visibility = 'shared' AND creditor_id IS NOT NULL AND debtor_id IS NOT NULL)",
            'debts_phone_format' => "counterparty_phone IS NULL OR counterparty_phone REGEXP '^[+][1-9][0-9]{1,14}$'",
            'debts_currency_format' => "currency COLLATE utf8mb4_bin REGEXP '^[A-Z]{3}$'",
        ],
        'debt_schedules' => [
            'schedules_amount_positive' => 'amount > 0',
            'schedules_sequence_positive' => 'sequence > 0',
        ],
        'payments' => [
            'payments_amount_positive' => 'amount > 0',
            'payments_confirmation_valid' => "(confirmation_status = 'confirmed' AND confirmed_by IS NOT NULL AND confirmed_at IS NOT NULL AND confirmed_by <> recorded_by) OR (confirmation_status <> 'confirmed' AND confirmed_by IS NULL AND confirmed_at IS NULL)",
        ],
        'payment_allocations' => [
            'allocations_amount_positive' => 'amount > 0',
        ],
        'agreement_versions' => [
            'versions_number_positive' => 'version > 0',
            'versions_creditor_consent' => 'creditor_accepted_at IS NULL OR creditor_id IS NOT NULL',
            'versions_debtor_consent' => 'debtor_accepted_at IS NULL OR debtor_id IS NOT NULL',
            'versions_distinct_parties' => 'creditor_id IS NULL OR debtor_id IS NULL OR creditor_id <> debtor_id',
            'versions_accepted_consent' => "agreement_status <> 'accepted' OR (creditor_id IS NOT NULL AND debtor_id IS NOT NULL AND creditor_accepted_at IS NOT NULL AND debtor_accepted_at IS NOT NULL)",
        ],
        'invitations' => [
            'invitations_phone_format' => "invitee_phone REGEXP '^[+][1-9][0-9]{1,14}$'",
            'invitations_accepted_identity' => "invitation_status <> 'accepted' OR (invitee_id IS NOT NULL AND responded_at IS NOT NULL)",
            'invitations_hash_format' => "token_hash REGEXP '^[0-9a-f]{64}$'",
        ],
    ];

    public function up(): void
    {
        foreach ($this->constraints as $table => $checks) {
            foreach ($checks as $name => $expression) {
                DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK ({$expression})");
            }
        }
    }

    public function down(): void
    {
        $version = DB::connection()->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
        $drop = str_contains($version, 'MariaDB') ? 'DROP CONSTRAINT' : 'DROP CHECK';

        foreach (array_reverse($this->constraints) as $table => $checks) {
            foreach (array_reverse($checks) as $name => $expression) {
                DB::statement("ALTER TABLE {$table} {$drop} {$name}");
            }
        }
    }
};
