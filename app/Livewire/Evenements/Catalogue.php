<?php

namespace App\Livewire\Evenements;

use App\Enums\ReservationStatus;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Transport aller-retour pour vos événements')]
class Catalogue extends Component
{
    /** Recherche libre : nom de l'événement, lieu ou ville. */
    #[Url(as: 'q', except: '')]
    public string $q = '';

    /** Filtre de période : tout, semaine, mois. */
    #[Url(as: 'quand', except: 'tout')]
    public string $quand = 'tout';

    public function updatedQuand(): void
    {
        if (! in_array($this->quand, ['tout', 'semaine', 'mois'], true)) {
            $this->quand = 'tout';
        }
    }

    public function effacer(): void
    {
        $this->reset('q', 'quand');
    }

    #[Computed]
    public function evenements(): Collection
    {
        $q = trim($this->q);

        return Evenement::publies()
            ->where(fn ($w) => $w->whereNull('fin_at')->where('debut_at', '>=', now()->subHours(6))
                ->orWhere('fin_at', '>=', now()))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s
                ->where('titre', 'like', '%'.$q.'%')
                ->orWhere('lieu', 'like', '%'.$q.'%')
                ->orWhere('ville', 'like', '%'.$q.'%')))
            ->when($this->quand === 'semaine', fn ($w) => $w->where('debut_at', '<=', now()->addDays(7)->endOfDay()))
            ->when($this->quand === 'mois', fn ($w) => $w->where('debut_at', '<=', now()->addDays(31)->endOfDay()))
            ->with([
                'departs' => fn ($d) => $d->ouverts()->with(['tarifs' => fn ($t) => $t->where('actif', true)]),
                'typesBillets' => fn ($t) => $t->where('actif', true),
            ])
            ->orderBy('debut_at')
            ->get();
    }

    /** Réservation en attente de paiement / vérification, pour proposer de la reprendre. */
    #[Computed]
    public function enCours(): ?Reservation
    {
        if (! Auth::check()) {
            return null;
        }

        return Auth::user()->reservations()
            ->with(['depart.evenement', 'typeBillet.evenement', 'zone'])
            ->whereIn('statut', [ReservationStatus::APayer->value, ReservationStatus::EnVerification->value])
            ->latest('id')
            ->first();
    }

    /** Photo de la bannière : réglée dans l'admin, sinon public/images/hero.jpg, sinon aucune. */
    #[Computed]
    public function heroImage(): ?string
    {
        if ($chemin = Setting::get('hero_image')) {
            return Storage::disk('public')->url($chemin);
        }

        return is_file(public_path('images/hero.jpg')) ? asset('images/hero.jpg') : null;
    }

    public function render()
    {
        return view('livewire.evenements.catalogue');
    }
}
