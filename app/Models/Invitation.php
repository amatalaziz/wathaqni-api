<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invitation extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $invitation): void {
            if ($invitation->isDirty(['agreement_version_id', 'invitable_type', 'invitable_id', 'invitee_phone', 'invitee_role'])) {
                throw new \DomainException('Issue a new invitation when changing the agreement version or recipient.');
            }
        });
    }

    public function agreementVersion(): BelongsTo
    {
        return $this->belongsTo(AgreementVersion::class);
    }

    /**
     * Call with a fresh invitation inside a transaction locking its subject.
     * Creating versions and accepting invitations must use that same subject lock.
     */
    public function assertCanBeAcceptedBy(User $user): void
    {
        $version = $this->agreementVersion()->firstOrFail();
        $hasNewerVersion = $version->newQuery()
            ->where('agreeable_type', $version->agreeable_type)
            ->where('agreeable_id', $version->agreeable_id)
            ->where('version', '>', $version->version)->exists();

        if ($this->invitation_status !== 'pending'
            || $this->expires_at === null || $this->expires_at->isPast()
            || $user->phone_verified_at === null || $user->phone !== $this->invitee_phone
            || ($this->invitee_id !== null && (string) $this->invitee_id !== (string) $user->getKey())
            || $version->agreement_status !== 'pending' || $hasNewerVersion) {
            throw new \DomainException('This invitation is expired, superseded, or not addressed to this verified user.');
        }
    }

    protected $fillable = [
        'invitee_phone',
        'invitee_role',
        'expires_at',
    ];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
        ];
    }

    public function invitable(): MorphTo
    {
        return $this->morphTo();
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    /**
     * Return a 256-bit token for delivery; persist only its SHA-256 hash.
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->token_hash = hash('sha256', $token);

        return $token;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('invitation_status', 'pending')->where('expires_at', '>', now());
    }
}
