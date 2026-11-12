<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Billet;
use App\Models\User;

class BilletPolicy
{
    public function view(User $user, Billet $billet): bool
    {
        if (in_array($user->role, [UserRole::Admin->value, UserRole::Controleur->value], true) && $user->actif) {
            return true;
        }

        return $billet->reservation?->user_id === $user->id;
    }

    public function scan(User $user): bool
    {
        return $user->peutScanner();
    }
}
