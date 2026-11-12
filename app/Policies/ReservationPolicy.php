<?php

namespace App\Policies;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->role === UserRole::Admin->value && $ability !== 'submitProof') {
            return true;
        }

        return null;
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function submitProof(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            && $reservation->statut === ReservationStatus::APayer
            && $user->actif;
    }
}
