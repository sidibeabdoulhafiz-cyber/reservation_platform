<?php

namespace App\Livewire\Pages;

use App\Livewire\Pages\Concerns\EnvoieMessage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.site')]
class Contact extends Component
{
    use EnvoieMessage;

    public string $nom = '';

    public string $email = '';

    public string $tel = '';

    public string $sujet = '';

    public string $message = '';

    public function mount(): void
    {
        if ($u = auth()->user()) {
            $this->nom = $u->name;
            $this->email = $u->email;
            $this->tel = (string) $u->tel;
        }
    }

    public function envoyer(): void
    {
        $donnees = $this->validate([
            'nom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'tel' => ['nullable', 'string', 'max:30'],
            'sujet' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], ['nom' => 'nom', 'email' => 'e-mail', 'sujet' => 'sujet', 'message' => 'message']);

        $this->enregistrerEtNotifier([...$donnees, 'type' => 'contact']);
    }

    public function render()
    {
        return view('livewire.pages.contact')
            ->title('Nous contacter')
            ->layoutData([
                'description' => 'Une question sur une réservation, un départ ou un paiement ? Écrivez à l’équipe CotaTrans, réponse rapide.',
            ]);
    }
}
