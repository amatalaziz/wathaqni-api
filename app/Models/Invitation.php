<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Invitation extends Model
{
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
