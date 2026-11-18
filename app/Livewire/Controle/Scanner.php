<?php

namespace App\Livewire\Controle;

use App\Actions\Reservations\ScanBillet;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.site')]
#[Title('Contrôle à l’entrée')]
class Scanner extends Component
{
    public string $numero = '';

    /** Sens du contrôle : « aller » (vers l'événement) ou « retour ». */
    public string $sens = 'aller';

    public ?string $statut = null;

    public ?string $message = null;

    public ?array $detail = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->peutScanner(), 403);
    }

    public function verifier(ScanBillet $scan): void
    {
        $this->validate([
            'numero' => ['required', 'string', 'min:8', 'max:20'],
        ], [
            'numero.required' => 'Scannez ou saisissez le numéro du billet.',
        ]);

        $resultat = $scan->handle($this->numero, Auth::user(), $this->sens);

        $this->statut = $resultat['statut'];
        $this->message = $resultat['message'];
        $billet = $resultat['billet'];
        $this->detail = $billet ? [
            'numero' => $billet->numero,
            'siege' => $billet->siege,
            'passager' => $billet->nom_passager,
            'evenement' => $billet->depart?->evenement?->titre ?? $billet->typeBillet?->evenement?->titre,
            'arret' => $billet->reservation?->zone?->nom,
            'aller' => $billet->aller_scanne_at?->format('H:i'),
            'retour' => $billet->retour_scanne_at?->format('H:i'),
            'sens' => $resultat['sens'],
        ] : null;
        $this->numero = '';
    }

    public function updatedSens(): void
    {
        if (! in_array($this->sens, ScanBillet::SENS, true)) {
            $this->sens = 'aller';
        }
        $this->reset('statut', 'message', 'detail');
    }

    public function render()
    {
        return view('livewire.controle.scanner');
    }
}
