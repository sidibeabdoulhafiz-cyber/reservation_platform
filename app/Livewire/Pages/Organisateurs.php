<?php

namespace App\Livewire\Pages;

use App\Livewire\Pages\Concerns\EnvoieMessage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.site')]
class Organisateurs extends Component
{
    use EnvoieMessage;

    public string $nom = '';

    public string $organisation = '';

    public string $email = '';

    public string $tel = '';

    public string $evenement = '';

    public string $date_evenement = '';

    public string $lieu = '';

    public string $participants = '';

    public string $message = '';

    public function envoyer(): void
    {
        $donnees = $this->validate([
            'nom' => ['required', 'string', 'max:120'],
            'organisation' => ['nullable', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190'],
            'tel' => ['required', 'string', 'max:30', 'regex:/^[\d\s+().-]{8,30}$/'],
            'evenement' => ['required', 'string', 'max:150'],
            'date_evenement' => ['nullable', 'date', 'after_or_equal:today'],
            'lieu' => ['nullable', 'string', 'max:150'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'message' => ['nullable', 'string', 'max:3000'],
        ], [
            'tel.regex' => 'Numéro de téléphone invalide.',
            'date_evenement.after_or_equal' => 'La date doit être aujourd’hui ou plus tard.',
        ], [
            'nom' => 'nom', 'email' => 'e-mail', 'tel' => 'téléphone', 'evenement' => 'nom de l’événement',
            'date_evenement' => 'date', 'participants' => 'nombre de participants',
        ]);

        $this->enregistrerEtNotifier([
            ...$donnees,
            'type' => 'organisateur',
            'date_evenement' => $donnees['date_evenement'] ?: null,
            'participants' => $donnees['participants'] ?: null,
        ]);
    }

    public function render()
    {
        return view('livewire.pages.organisateurs')
            ->title('Organisateurs : proposez le transport de votre événement')
            ->layoutData([
                'description' => 'Organisateur de concert, festival, match ou sortie ? Confiez le transport aller-retour de votre public à CotaTrans. Envoyez-nous votre événement en 1 minute.',
            ]);
    }
}
