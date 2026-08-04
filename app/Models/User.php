<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'username',
    'password',
    'mobile_number',
    'role',
    'department_type',
    'department_name',
    'is_active',
])]
#[Hidden([
    'password',
    'remember_token',
    'two_factor_secret',
    'two_factor_recovery_codes',
])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public const DEPARTMENTS = [
        'fire' => 'Fire Department',
        'medical' => 'Medical Department',
        'natural_disaster' => 'Disaster Risk Department',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isResponder(): bool
    {
        return $this->role === 'responder';
    }

    public function isCitizen(): bool
    {
        return ($this->role ?? 'citizen') === 'citizen';
    }

    public function departmentLabel(): string
    {
        return self::DEPARTMENTS[$this->department_type] ?? ($this->department_name ?: 'Responder');
    }

    public function initials(): string
    {
        if (! empty($this->first_name) || ! empty($this->last_name)) {
            return Str::upper(
                Str::substr((string) $this->first_name, 0, 1).
                Str::substr((string) $this->last_name, 0, 1)
            );
        }

        if (! empty($this->name)) {
            return Str::of($this->name)
                ->explode(' ')
                ->take(2)
                ->map(fn ($word) => Str::substr($word, 0, 1))
                ->implode('');
        }

        if (! empty($this->username)) {
            return Str::upper(Str::substr($this->username, 0, 2));
        }

        return Str::substr((string) $this->mobile_number, -2);
    }

    public function emergencyReports(): HasMany
    {
        return $this->hasMany(EmergencyReport::class);
    }
}
