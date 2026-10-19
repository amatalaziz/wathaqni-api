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

    public function confirmedPayments(): HasMany
    {
        return $this->payments()->where('confirmation_status', 'confirmed');
    }
}
