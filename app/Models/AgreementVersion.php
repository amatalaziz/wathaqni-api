<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AgreementVersion extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->isDirty(['terms', 'version', 'agreeable_type', 'agreeable_id'])) {
                throw new \DomainException('Create a new agreement version instead of changing its terms or subject.');
            }
        });
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    protected $fillable = [
        'version',
        'terms',
    ];

    protected function casts(): array
    {
        return [
            'terms' => 'array',
            'creditor_accepted_at' => 'datetime',
            'debtor_accepted_at' => 'datetime',
        ];
    }

    public function agreeable(): MorphTo
    {
        return $this->morphTo();
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
}
