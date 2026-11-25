<?php

namespace Tests\Feature;

use App\Mail\CodeVerification;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailsEtMesReservationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_e_mail_au_theme_cotatrans_et_vouvoiement(): void
    {
        $html = (new CodeVerification(User::factory()->create(['name' => 'Awa Koné']), '482913'))->render();

        $this->assertStringContainsString('images/logo-email.png', $html);
        $this->assertStringContainsString('#FB7212', $html);
        $this->assertStringContainsString('Voici votre code', $html);
        $this->assertStringNotContainsString(' ton ', $html);
    }

    public function test_e_mails_facultatifs_desactivables(): void
    {
        $this->assertTrue(\App\Support\Notifier::actif('mail_paiement_recu'));
        Setting::set('mail_paiement_recu', '0');
        $this->assertFalse(\App\Support\Notifier::actif('mail_paiement_recu'));
    }

    public function test_page_code_attend_le_bouton_valider(): void
    {
        config(['cotatrans.code_connexion' => true]);
        \Illuminate\Support\Facades\Mail::fake();
        $user = User::factory()->create(['role' => 'client', 'actif' => true]);

        $this->actingAs($user)->get(route('code.show'))->assertOk()
            ->assertSee('Valider le code')
            ->assertDontSee('requestSubmit', false);
    }

    public function test_formulaires_sans_identifiants_de_demo(): void
    {
        $this->get('/login')->assertOk()->assertDontSee('cotatrans.test')->assertDontSee('mot de passe <span', false);
        $this->get('/register')->assertOk()->assertDontSee('password_confirmation', false);
    }

    public function test_mes_reservations_vide(): void
    {
        $user = User::factory()->create(['role' => 'client', 'actif' => true]);
        $this->actingAs($user)->get(route('reservations.index'))->assertOk()
            ->assertSee('Aucune réservation pour l’instant', false)
            ->assertSee('Voir les événements');
    }

    public function test_page_de_paiement_ouvre_wave_avec_le_montant_exact(): void
    {
        Setting::set('site_ouvert', '1');
        Setting::set('wave_link', 'https://pay.wave.com/m/M_ci_0S9vrDPL2ExV/c/ci/?amount=100');
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $z = \App\Models\Zone::create(['nom' => 'Cocody', 'prix_defaut' => 3000, 'actif' => true]);
        $v = \App\Models\Vehicule::create(['nom' => 'Car', 'immatriculation' => 'W-1', 'capacite' => 30, 'actif' => true]);
        $evt = \App\Models\Evenement::create(['titre' => 'Femua', 'slug' => 'femua', 'lieu' => 'Anoumabo', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(6), 'statut' => 'publie']);
        $d = \App\Models\Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays(6), 'places_total' => 30, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z->id, 'ordre' => 1, 'principal' => true, 'prix' => 3000, 'actif' => true]);
        $r = app(\App\Actions\Reservations\CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $d->id, 'zone_id' => $z->id, 'quantite' => 2]);

        $this->actingAs($client)->get(route('reservations.show', $r))->assertOk()
            ->assertSee('href="https://pay.wave.com/m/M_ci_0S9vrDPL2ExV/c/ci/?amount=6000"', false)
            ->assertDontSee('amount=100', false)
            ->assertSee('Payer avec Wave');
    }

    public function test_changer_d_arret_change_le_prix(): void
    {
        Setting::set('site_ouvert', '1');
        $z1 = \App\Models\Zone::create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'actif' => true]);
        $z2 = \App\Models\Zone::create(['nom' => 'Cocody', 'prix_defaut' => 3000, 'actif' => true]);
        $v = \App\Models\Vehicule::create(['nom' => 'Car', 'immatriculation' => 'S-1', 'capacite' => 30, 'actif' => true]);
        $evt = \App\Models\Evenement::create(['titre' => 'Match', 'slug' => 'match', 'lieu' => 'Stade', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(6), 'statut' => 'publie']);
        $d = \App\Models\Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays(6), 'places_total' => 30, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z1->id, 'ordre' => 1, 'principal' => true, 'prix' => 2500, 'actif' => true]);
        $d->tarifs()->create(['zone_id' => $z2->id, 'ordre' => 2, 'principal' => false, 'prix' => 3000, 'actif' => true]);

        Livewire::test(\App\Livewire\Reservations\Create::class, ['evenement' => $evt])
            ->assertSet('zone_id', $z1->id)
            ->assertSee('wire:click="choisirArret('.$z2->id.')"', false)
            ->call('choisirArret', $z2->id)
            ->assertSet('zone_id', $z2->id)
            ->assertSee('aria-checked="true"', false)
            ->call('increment')
            ->assertSee('6 000 F')
            ->call('choisirArret', 999999)
            ->assertSet('zone_id', $z2->id);
    }

    public function test_nom_obligatoire_pour_chaque_invite(): void
    {
        Setting::set('site_ouvert', '1');
        $client = User::factory()->create(['role' => 'client', 'actif' => true, 'name' => 'Awa Koné']);
        $z = \App\Models\Zone::create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'actif' => true]);
        $v = \App\Models\Vehicule::create(['nom' => 'Car', 'immatriculation' => 'P-1', 'capacite' => 30, 'actif' => true]);
        $evt = \App\Models\Evenement::create(['titre' => 'Match', 'slug' => 'match-p', 'lieu' => 'Stade', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(6), 'statut' => 'publie']);
        $d = \App\Models\Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays(6), 'places_total' => 30, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z->id, 'ordre' => 1, 'principal' => true, 'prix' => 2500, 'actif' => true]);

        $this->actingAs($client);
        Livewire::test(\App\Livewire\Reservations\Create::class, ['evenement' => $evt])
            ->assertSet('prixU', 2500)
            ->set('quantite', 3)
            ->set('passagers', ['', 'Moussa Traoré', '  '])
            ->call('reserve')
            ->assertHasErrors(['passagers.2'])
            ->set('passagers', ['', 'Moussa Traoré', '12'])
            ->call('reserve')
            ->assertHasErrors(['passagers.2'])
            ->set('passagers', ['', 'Moussa Traoré', 'Koffi Yao'])
            ->call('reserve')
            ->assertHasNoErrors();

        $r = \App\Models\Reservation::latest('id')->first();
        $this->assertSame(['Awa Koné', 'Moussa Traoré', 'Koffi Yao'], $r->passagers);
        $this->assertSame(7500, (int) $r->montant);
    }
}
