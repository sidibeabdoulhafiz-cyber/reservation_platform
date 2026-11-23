<?php

namespace Tests\Feature;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Actions\Reservations\ValidatePayment;
use App\Enums\ReservationStatus;
use App\Livewire\Reservations\Create;
use App\Livewire\Reservations\Suivi;
use App\Mail\BilletsConfirmes;
use App\Mail\PaiementRecu;
use App\Mail\ReservationCreee;
use App\Mail\Transport\BrevoTransport;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use App\Support\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ParcoursPassagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_accueil_affiche_les_evenements_avec_leur_affiche(): void
    {
        Storage::fake('public');
        [$evt] = $this->catalogue();
        $evt->update(['image_path' => 'evenements/affiche.jpg']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Concert test')
            ->assertSee('evenements/affiche.jpg', false)
            ->assertSee('2 500', false)
            ->assertSee(route('evenements.show', $evt), false);
    }

    public function test_page_evenement_montre_l_itineraire_dans_l_ordre(): void
    {
        [$evt] = $this->catalogue();

        $this->get(route('evenements.show', $evt))
            ->assertOk()
            ->assertSeeInOrder(['Yopougon', 'Cocody'])
            ->assertSee('Principal')
            ->assertSee('Qui voyage ?')
            ->assertSee('Créer mon compte et réserver');
    }

    public function test_evenement_brouillon_introuvable(): void
    {
        [$evt] = $this->catalogue();
        $evt->update(['statut' => 'brouillon']);

        $this->get(route('evenements.show', $evt))->assertNotFound();
    }

    public function test_un_visiteur_reserve_en_creant_son_compte_sur_la_meme_page(): void
    {
        Mail::fake();
        [$evt, $depart, $yop] = $this->catalogue();

        Livewire::test(Create::class, ['evenement' => $evt])
            ->assertSet('depart_id', $depart->id)
            ->assertSet('zone_id', $yop->id) // point principal présélectionné
            ->call('increment')
            ->set('nom', 'Awa Koné')
            ->set('tel', '07 00 00 00 99')
            ->set('email', 'Awa@Exemple.com')
            ->set('password', 'motdepasse123')
            ->call('reserve')
            ->assertHasNoErrors()
            ->assertRedirect();

        $user = User::where('email', 'awa@exemple.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('0700000099', $user->tel);

        $reservation = $user->reservations()->firstOrFail();
        $this->assertSame(2, $reservation->quantite);
        $this->assertSame(5000, $reservation->montant);
        $this->assertSame(2, $depart->fresh()->places_reservees);

        Mail::assertSent(ReservationCreee::class, fn ($m) => $m->hasTo('awa@exemple.com'));
    }

    public function test_inscription_integree_refuse_un_email_deja_pris(): void
    {
        [$evt] = $this->catalogue();
        User::factory()->create(['email' => 'pris@exemple.com']);

        Livewire::test(Create::class, ['evenement' => $evt])
            ->set('nom', 'X')->set('tel', '0700000000')->set('email', 'pris@exemple.com')->set('password', 'motdepasse123')
            ->call('reserve')
            ->assertHasErrors(['email']);

        $this->assertSame(0, Reservation::count());
    }

    public function test_connexion_integree_puis_reservation(): void
    {
        Mail::fake();
        [$evt] = $this->catalogue();
        $user = User::factory()->create(['email' => 'deja@exemple.com', 'password' => 'motdepasse123', 'role' => 'client', 'actif' => true]);

        Livewire::test(Create::class, ['evenement' => $evt])
            ->set('mode', 'connexion')
            ->set('email', 'deja@exemple.com')
            ->set('password', 'motdepasse123')
            ->call('reserve')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, $user->reservations()->count());
    }

    public function test_suivi_reserve_au_proprietaire_et_envoi_de_la_preuve(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$evt, $depart, $yop] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $autre = User::factory()->create(['role' => 'client', 'actif' => true]);
        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $yop->id, 'quantite' => 1]);

        $this->actingAs($autre)->get(route('reservations.show', $r))->assertForbidden();
        $this->actingAs($client)->get(route('reservations.show', $r))->assertOk()->assertSee('Ouvrir Wave et payer');

        Livewire::actingAs($client)->test(Suivi::class, ['reservation' => $r])
            ->set('capture', UploadedFile::fake()->image('recu.jpg'))
            ->set('transaction_id', 't_abc 12345')
            ->set('montant_declare', (int) $r->montant + 500)
            ->call('submitProof')
            ->assertHasErrors(['montant_declare'])
            ->set('montant_declare', (int) $r->montant)
            ->call('submitProof')
            ->assertHasNoErrors()
            ->assertSee('Reçu bien reçu');

        $this->assertSame(ReservationStatus::EnVerification, $r->fresh()->statut);
        Mail::assertSent(PaiementRecu::class);
    }

    public function test_client_peut_annuler_une_reservation_a_payer(): void
    {
        [$evt, $depart, $yop] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $yop->id, 'quantite' => 3]);
        $this->assertSame(3, $depart->fresh()->places_reservees);

        Livewire::actingAs($client)->test(Suivi::class, ['reservation' => $r])->call('annuler');

        $this->assertSame(ReservationStatus::Annulee, $r->fresh()->statut);
        $this->assertSame(0, $depart->fresh()->places_reservees);
    }

    public function test_validation_envoie_les_billets_avec_qr_en_piece_jointe_et_page_imprimable(): void
    {
        Storage::fake('local');
        Mail::fake();
        [$evt, $depart, $yop] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);

        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $yop->id, 'quantite' => 2]);
        $p = app(SubmitPaymentProof::class)->handle($r, 5000, 'T_VALID001', UploadedFile::fake()->image('r.jpg'));

        $this->actingAs($client)->get(route('reservations.imprimer', $r))->assertNotFound();

        $payee = app(ValidatePayment::class)->handle($p, $admin);
        Notifier::billetsConfirmes($payee);

        Mail::assertSent(BilletsConfirmes::class, function (BilletsConfirmes $m) use ($payee) {
            $html = $m->render();

            return $m->hasTo($payee->user->email)
                && str_contains($html, $payee->billets[0]->numero)
                && str_contains($html, 'Gare de Yopougon')
                && (! extension_loaded('gd') || count($m->attachments()) === 2);
        });

        $this->actingAs($client)->get(route('reservations.imprimer', $r))
            ->assertOk()
            ->assertSee($payee->billets[1]->numero)
            ->assertSee('Imprimer ou enregistrer en PDF');

        $this->actingAs($client)->get(route('reservations.index'))->assertOk()->assertSee('Concert test');
    }

    public function test_transport_brevo_appelle_l_api_avec_le_bon_contenu(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['messageId' => '<abc@brevo>'], 201)]);
        config(['mail.mailers.brevo.key' => 'cle-test', 'mail.default' => 'brevo', 'mail.from.address' => 'billets@cotatrans.ci', 'mail.from.name' => 'CotaTrans']);

        Mail::raw('Bonjour', function ($m) {
            $m->to('client@exemple.com', 'Client')->subject('Sujet test')->attachData('PNG', 'billet.png');
        });

        Http::assertSent(function (Request $req) {
            $d = $req->data();

            return $req->url() === 'https://api.brevo.com/v3/smtp/email'
                && $req->hasHeader('api-key', 'cle-test')
                && $d['sender']['email'] === 'billets@cotatrans.ci'
                && $d['to'][0] === ['email' => 'client@exemple.com', 'name' => 'Client']
                && $d['subject'] === 'Sujet test'
                && str_contains($d['textContent'] ?? $d['htmlContent'] ?? '', 'Bonjour')
                && $d['attachment'][0]['name'] === 'billet.png'
                && base64_decode($d['attachment'][0]['content']) === 'PNG';
        });
    }

    public function test_transport_brevo_signale_une_erreur_api(): void
    {
        Http::fake(['api.brevo.com/*' => Http::response(['message' => 'Key not found'], 401)]);
        $transport = new BrevoTransport('mauvaise-cle');

        $this->expectExceptionMessage('Key not found');
        $transport->send((new Email)->from('a@b.ci')->to('c@d.ci')->subject('x')->text('y'));
    }

    public function test_le_formulaire_paiement_garde_intact_son_javascript_ocr(): void
    {
        [$evt, $depart, $yop] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $yop->id, 'quantite' => 1]);

        // Tester le composant Suivi (paiement) : pas de JS brut exposé
        $html = Livewire::actingAs($client)->test(Suivi::class, ['reservation' => $r])->html();
        $this->assertStringNotContainsString('parseInt', $html, 'Du code JavaScript brut (parseInt) est affiché dans le HTML');
        $this->assertStringNotContainsString('extraireDepuisTexte', $html);
        $this->assertStringNotContainsString('Tesseract.createWorker', $html);

        // Tester la page d'accueil (Catalogue) : section "Comment ça marche" interactive
        $html = $this->get('/')->assertOk()->getContent();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

        // Vérifier la présence de la section interactive
        $section = $dom->getElementById('comment-ca-marche');
        $this->assertNotNull($section, 'La section "Comment ça marche" doit exister');

        // Le trajet en 4 arrêts : léger, sans minuterie JS ni maquette de téléphone
        $this->assertSame(4, $section->getElementsByTagName('ol')->item(0)?->getElementsByTagName('li')->length);
        $this->assertStringContainsString('rt-bus', $html);
        $this->assertStringNotContainsString('class="phone"', $html);
    }

    /** @return array{0: Evenement, 1: Depart, 2: Zone, 3: Zone} */
    private function catalogue(): array
    {
        Setting::set('site_ouvert', '1');
        Setting::set('delai_paiement_min', '30');

        $yop = Zone::create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'ordre' => 1, 'actif' => true]);
        $coc = Zone::create(['nom' => 'Cocody', 'prix_defaut' => 3000, 'ordre' => 2, 'actif' => true]);
        $vehicule = Vehicule::create(['nom' => 'Car 1', 'immatriculation' => 'T-1', 'capacite' => 18, 'actif' => true]);
        $evt = Evenement::create([
            'titre' => 'Concert test', 'slug' => 'concert-test', 'lieu' => 'Plateau', 'ville' => 'Abidjan',
            'debut_at' => now()->addDays(5)->setTime(20, 0), 'statut' => 'publie',
        ]);
        $depart = Depart::create([
            'evenement_id' => $evt->id, 'vehicule_id' => $vehicule->id, 'depart_at' => now()->addDays(5)->setTime(18, 0),
            'places_total' => 18, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert',
        ]);
        // Créés dans le désordre : l'ordre d'affichage suit la colonne « ordre ».
        $depart->tarifs()->create(['zone_id' => $coc->id, 'ordre' => 2, 'prix' => 3000, 'heure_ramassage' => '17:20', 'actif' => true]);
        $depart->tarifs()->create(['zone_id' => $yop->id, 'ordre' => 1, 'principal' => true, 'prix' => 2500, 'point_rdv' => 'Gare de Yopougon', 'heure_ramassage' => '17:00', 'actif' => true]);

        return [$evt, $depart, $yop, $coc];
    }
}
