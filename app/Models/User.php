<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'tel', 'role', 'actif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === UserRole::Admin->value && $this->actif;
    }

    public function estAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    public function peutScanner(): bool
    {
        return in_array($this->role, [UserRole::Admin->value, UserRole::Controleur->value], true)
            && $this->actif;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'actif' => 'boolean',
        ];
    }
}
