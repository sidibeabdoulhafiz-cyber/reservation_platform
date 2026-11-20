<?php

namespace Tests\Feature;

use App\Actions\Reservations\CreateReservation;
use App\Enums\ReservationStatus;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesDonneesTest extends TestCase
{
    use RefreshDatabase;

    private function reservation(User $user, int $dansJours = 5)
    {
        Setting::set('site_ouvert', '1');
        $z = Zone::create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'actif' => true]);
        $v = Vehicule::create(['nom' => 'Car', 'immatriculation' => 'T-'.rand(1, 9999), 'capacite' => 20, 'actif' => true]);
        $evt = Evenement::create(['titre' => 'Concert', 'slug' => 'c-'.rand(1, 9999), 'lieu' => 'Plateau', 'ville' => 'Abidjan', 'debut_at' => now()->addDays($dansJours), 'statut' => 'publie']);
        $d = Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays($dansJours), 'places_total' => 20, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z->id, 'ordre' => 1, 'principal' => true, 'prix' => 2500, 'actif' => true]);

        return app(CreateReservation::class)->handle($user, ['type' => 'transport', 'depart_id' => $d->id, 'zone_id' => $z->id, 'quantite' => 2, 'passagers' => ['Awa Koné', 'Moussa Traoré']]);
    }

    public function test_export_de_mes_donnees(): void
    {
        $user = User::factory()->create(['name' => 'Awa Koné', 'role' => 'client', 'actif' => true]);
        $this->reservation($user);

        $this->actingAs($user)->get(route('profile.export'))
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename="mes-donnees-cotatrans.json"')
            ->assertJsonPath('compte.nom', 'Awa Koné');
    }

    public function test_suppression_bloquee_si_voyage_a_venir(): void
    {
        $user = User::factory()->create(['role' => 'client', 'actif' => true]);
        $this->reservation($user);

        $this->actingAs($user)->from('/profile')
            ->delete('/profile', ['password' => 'password', 'confirmation' => '1'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertTrue($user->fresh()->actif);
    }

    public function test_suppression_anonymise_les_donnees_apres_le_voyage(): void
    {
        $user = User::factory()->create(['name' => 'Awa Koné', 'email' => 'awa@exemple.ci', 'role' => 'client', 'actif' => true]);
        $r = $this->reservation($user);
        $r->update(['statut' => ReservationStatus::Annulee->value]);

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password', 'confirmation' => '1'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $u = $user->fresh();
        $this->assertSame('Compte supprimé', $u->name);
        $this->assertStringEndsWith('@anonyme.invalid', $u->email);
        $this->assertFalse($u->actif);
        $this->assertNull($r->fresh()->passagers);
        $this->assertDatabaseMissing('users', ['email' => 'awa@exemple.ci']);
    }

    public function test_la_case_de_confirmation_est_obligatoire(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->from('/profile')
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'confirmation');
    }
}
