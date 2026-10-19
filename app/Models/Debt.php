<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Debt extends Model
{
    protected $fillable = [
        'created_by',
        'creditor_id',
        'debtor_id',
        'counterparty_name',
        'counterparty_phone',
        'amount',
        'currency',
        'debt_date',
        'due_date',
        'description',
        'visibility',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'debt_date' => 'date',
            'due_date' => 'date',
            'shared_balance' => 'decimal:4',
            'balance' => 'decimal:4',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creditor_id');
    }

    public function debtor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'debtor_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DebtSchedule::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invitations(): MorphMany
    {
        return $this->morphMany(Invitation::class, 'invitable');
    }

    public function agreementVersions(): MorphMany
    {
        return $this->morphMany(AgreementVersion::class, 'agreeable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function activityEvents(): MorphMany
    {
        return $this->morphMany(ActivityEvent::class, 'subject');
    }

    /**
     * SQL DECIMAL arithmetic; pending/rejected payments never affect this balance.
     * This is a shared ledger view only for an accepted, shared agreement.
     */
    public function scopeWithSharedBalance(Builder $query): Builder
    {
        return $query->where('debts.visibility', 'shared')
            ->where('debts.agreement_status', 'accepted')
            ->addSelect('debts.*')
            ->selectRaw("(debts.amount - COALESCE((SELECT SUM(payments.amount) FROM payments WHERE payments.debt_id = debts.id AND payments.confirmation_status = 'confirmed'), 0)) AS shared_balance");
    }

    public function scopeWithCurrentBalance(Builder $query): Builder
    {
        return $query->addSelect('debts.*')
            ->selectRaw("CASE WHEN debts.visibility <> 'shared' OR debts.agreement_status = 'accepted' THEN debts.amount - COALESCE((SELECT SUM(payments.amount) FROM payments WHERE payments.debt_id = debts.id AND payments.confirmation_status = 'confirmed'), 0) ELSE debts.amount END AS balance");
    }

    public function confirmedPayments(): HasMany
    {
        return $this->payments()->where('confirmation_status', 'confirmed');
    }

    /** Acquire this lock BEFORE any payment, allocation, schedule or principal change. */
    public static function lockForLedgerUpdate(int $id): self
    {
        $model = new static;
        if ($model->getConnection()->transactionLevel() === 0) {
            throw new \LogicException('Ledger changes require a database transaction.');
        }

        return $model->newQuery()->lockForUpdate()->findOrFail($id);
    }

    /**
     * Validate persisted changes before committing the transaction holding the debt lock.
     * Let failures roll back the entire transaction. All writers must use this protocol;
     * CHECK constraints cannot enforce aggregates across rows, nor can model events
     * safely validate a multi-row operation before all its changes have been written.
     * Pending payments reserve capacity but only confirmed payments reduce the balance.
     */
    public function assertLedgerWithinLimits(): void
    {
        if ($this->getConnection()->transactionLevel() === 0) {
            throw new \LogicException('Ledger validation requires the transaction holding the debt lock.');
        }

        $principalExceeded = $this->newQuery()->whereKey($this->getKey())
            ->where(function (Builder $query): void {
                $query->whereRaw("(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE debt_id = debts.id AND confirmation_status <> 'rejected') > debts.amount")
                    ->orWhereRaw('(SELECT COALESCE(SUM(amount), 0) FROM debt_schedules WHERE debt_id = debts.id) > debts.amount');
            })->exists();

        $paymentExceeded = $this->payments()
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payment_allocations WHERE payment_id = payments.id) > payments.amount')
            ->exists();

        $scheduleExceeded = $this->schedules()
            ->whereRaw("(SELECT COALESCE(SUM(payment_allocations.amount), 0) FROM payment_allocations INNER JOIN payments ON payments.id = payment_allocations.payment_id WHERE payment_allocations.debt_schedule_id = debt_schedules.id AND payments.confirmation_status <> 'rejected') > debt_schedules.amount")
            ->exists();

        if ($principalExceeded || $paymentExceeded || $scheduleExceeded) {
            throw new \DomainException('Payments, schedules or allocations exceed the debt, payment or installment amount.');
        }
    }
}
