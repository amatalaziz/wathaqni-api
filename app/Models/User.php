<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function phone(): Attribute
    {
        return Attribute::make(set: function (?string $value): array {
            $phone = $value === null ? null : preg_replace('/[\s().-]+/', '', $value);

            if ($phone !== null && ! preg_match('/^\+[1-9][0-9]{1,14}$/D', $phone)) {
                throw new \InvalidArgumentException('Phone must include its country code in E.164 format.');
            }

            $attributes = ['phone' => $phone];
            if ($phone !== ($this->attributes['phone'] ?? null)) {
                $attributes['phone_verified_at'] = null;
            }

            return $attributes;
        });
    }

    public function createdDebts(): HasMany
    {
        return $this->hasMany(Debt::class, 'created_by');
    }

    public function creditorDebts(): HasMany
    {
        return $this->hasMany(Debt::class, 'creditor_id');
    }

    public function debtorDebts(): HasMany
    {
        return $this->hasMany(Debt::class, 'debtor_id');
    }

    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    public function receivedInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invitee_id');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }
}
