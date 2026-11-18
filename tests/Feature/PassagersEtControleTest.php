<?php

namespace Tests\Feature;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\ScanBillet;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Actions\Reservations\ValidatePayment;
use App\Enums\BilletStatus;
use App\Livewire\Controle\Scanner;
use App\Livewire\Evenements\Catalogue;
use App\Livewire\Reservations\Create;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Reservation;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PassagersEtControleTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_payeur_ajoute_des_passagers_invites_et_chacun_a_son_billet(): void
    {
        Mail::fake();
        Storage::fake('local');
        [$evt, $depart] = $this->catalogue();
        $payeur = User::factory()->create(['name' => 'Awa Koné', 'role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);

        Livewire::actingAs($payeur)->test(Create::class, ['evenement' => $evt])
            ->call('increment')
            ->call('increment')
            ->assertCount('passagers', 3)
            ->set('passagers.1', 'Moussa Traoré')
            ->set('passagers.2', 'Fatou Diallo')
            ->call('reserve')
            ->assertHasNoErrors()
            ->assertRedirect();

        $r = Reservation::firstOrFail();
        $this->assertSame(['Awa Koné', 'Moussa Traoré', 'Fatou Diallo'], $r->nomsPassagers());

        $p = app(SubmitPaymentProof::class)->handle($r, 7500, 'T_PAX00001', UploadedFile::fake()->image('r.jpg'));
        $payee = app(ValidatePayment::class)->handle($p, $admin);

        $this->assertSame(['Awa Koné', 'Moussa Traoré', 'Fatou Diallo'], $payee->billets->pluck('nom_passager')->all());
        $this->assertCount(3, $payee->billets->pluck('numero')->unique());
        $this->assertSame(1, User::where('role', 'client')->count(), 'Les invités n’ont pas de compte.');
    }

    public function test_retirer_un_passager_libere_une_place(): void
    {
        [$evt] = $this->catalogue();
        $payeur = User::factory()->create(['role' => 'client', 'actif' => true]);

        Livewire::actingAs($payeur)->test(Create::class, ['evenement' => $evt])
            ->call('increment')->call('increment')
            ->set('passagers.1', 'B')->set('passagers.2', 'C')
            ->call('retirerPassager', 1)
            ->assertSet('quantite', 2)
            ->assertSet('passagers', ['', 'C']);
    }

    public function test_controles_aller_et_retour_distincts(): void
    {
        Storage::fake('local');
        [, $depart, $zone] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $agent = User::factory()->create(['role' => 'controleur', 'actif' => true]);

        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $zone->id, 'quantite' => 1]);
        $p = app(SubmitPaymentProof::class)->handle($r, 2500, 'T_AR000001', UploadedFile::fake()->image('r.jpg'));
        $billet = app(ValidatePayment::class)->handle($p, $admin)->billets->first();

        Livewire::actingAs($agent)->test(Scanner::class)
            ->set('sens', 'aller')->set('numero', $billet->numero)->call('verifier')
            ->assertSet('statut', 'valide')->assertSee('Aller validé')
            ->set('numero', $billet->numero)->call('verifier')
            ->assertSet('statut', 'utilise')
            ->set('sens', 'retour')->set('numero', $billet->numero)->call('verifier')
            ->assertSet('statut', 'valide')->assertSee('Retour validé');

        $billet->refresh();
        $this->assertNotNull($billet->aller_scanne_at);
        $this->assertNotNull($billet->retour_scanne_at);
        $this->assertSame(BilletStatus::Utilise, $billet->statut);
        $this->assertSame('utilise', app(ScanBillet::class)->handle($billet->numero, $agent, 'aller')['statut']);
    }

    public function test_recherche_et_filtre_des_evenements(): void
    {
        $this->catalogue();
        Evenement::create(['titre' => 'Match ASEC', 'slug' => 'match-asec', 'lieu' => 'Stade FHB', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(20), 'statut' => 'publie']);

        Livewire::test(Catalogue::class)
            ->assertSee('Concert test')->assertSee('Match ASEC')
            ->set('q', 'stade')
            ->assertSee('Match ASEC')->assertDontSee('Concert test')
            ->set('q', '')->set('quand', 'semaine')
            ->assertSee('Concert test')->assertDontSee('Match ASEC')
            ->call('effacer')
            ->assertSee('Match ASEC');
    }

    public function test_espace_client_met_en_avant_le_prochain_depart(): void
    {
        Storage::fake('local');
        [, $depart, $zone] = $this->catalogue();
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $depart->id, 'zone_id' => $zone->id, 'quantite' => 1]);
        $p = app(SubmitPaymentProof::class)->handle($r, 2500, 'T_NEXT0001', UploadedFile::fake()->image('r.jpg'));
        app(ValidatePayment::class)->handle($p, $admin);

        $this->actingAs($client)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Prochain départ')
            ->assertSee('Concert test')
            ->assertSee('Retour');
    }

    /** @return array{0: Evenement, 1: Depart, 2: Zone} */
    private function catalogue(): array
    {
        Setting::set('site_ouvert', '1');
        $zone = Zone::create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'ordre' => 1, 'actif' => true]);
        $vehicule = Vehicule::create(['nom' => 'Car 1', 'immatriculation' => 'T-9', 'capacite' => 18, 'actif' => true]);
        $evt = Evenement::create(['titre' => 'Concert test', 'slug' => 'concert-test', 'lieu' => 'Plateau', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(3)->setTime(20, 0), 'statut' => 'publie']);
        $depart = Depart::create([
            'evenement_id' => $evt->id, 'vehicule_id' => $vehicule->id, 'depart_at' => now()->addDays(3)->setTime(18, 0),
            'retour_at' => now()->addDays(4)->setTime(1, 0), 'places_total' => 18, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert',
        ]);
        $depart->tarifs()->create(['zone_id' => $zone->id, 'ordre' => 1, 'principal' => true, 'prix' => 2500, 'heure_ramassage' => '17:00', 'actif' => true]);

        return [$evt, $depart, $zone];
    }
}
