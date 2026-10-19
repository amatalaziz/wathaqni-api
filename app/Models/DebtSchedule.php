<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebtSchedule extends Model
{
    protected $fillable = [
        'debt_id',
        'sequence',
        'amount',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:4',
            'due_date' => 'date',
        ];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    public function confirmedAllocations(): HasMany
    {
        return $this->allocations()->whereHas('payment', fn ($query) => $query->confirmed());
    }
}
