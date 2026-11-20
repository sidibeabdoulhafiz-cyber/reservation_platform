<?php

namespace Tests\Feature;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Actions\Reservations\ValidatePayment;
use App\Filament\Resources\Evenements\EvenementResource;
use App\Filament\Resources\Evenements\Pages\CreateEvenement;
use App\Filament\Resources\Evenements\Pages\EditEvenement;
use App\Filament\Resources\Evenements\RelationManagers\DepartsRelationManager;
use App\Mail\RappelDepart;
use App\Models\Depart;
use App\Models\Evenement;
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

class RappelsEtAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_rappels_veille_et_deux_heures_avant_envoyes_une_seule_fois(): void
    {
        Mail::fake();
        Storage::fake('local');
        Setting::set('site_ouvert', '1');
        $zone = Zone::create(['nom' => 'Cocody', 'prix_defaut' => 2000, 'actif' => true]);
        $v = Vehicule::create(['nom' => 'Car', 'immatriculation' => 'X', 'capacite' => 20, 'actif' => true]);
        $evt = Evenement::create(['titre' => 'Fête', 'slug' => 'fete', 'lieu' => 'Stade', 'ville' => 'Abidjan', 'debut_at' => now()->addDay()->addHours(2), 'statut' => 'publie']);
        $demain = Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDay(), 'places_total' => 20, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $bientot = Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addMinutes(100), 'places_total' => 20, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        foreach ([$demain, $bientot] as $d) {
            $d->tarifs()->create(['zone_id' => $zone->id, 'ordre' => 1, 'principal' => true, 'prix' => 2000, 'heure_ramassage' => $d->depart_at->format('H:i'), 'actif' => true]);
        }
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        foreach ([$demain, $bientot] as $i => $d) {
            $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $d->id, 'zone_id' => $zone->id, 'quantite' => 1]);
            $p = app(SubmitPaymentProof::class)->handle($r, 2000, 'T_RAPPEL00'.$i, UploadedFile::fake()->image("r{$i}.jpg", 10 + $i, 10));
            app(ValidatePayment::class)->handle($p, $admin);
        }

        $this->artisan('reservations:rappels')->assertSuccessful();
        Mail::assertSent(RappelDepart::class, 2);
        Mail::assertSent(RappelDepart::class, fn ($m) => $m->moment === 'veille');
        Mail::assertSent(RappelDepart::class, fn ($m) => $m->moment === 'depart');

        $this->artisan('reservations:rappels')->assertSuccessful();
        Mail::assertSent(RappelDepart::class, 2); // pas de doublon
    }

    public function test_configuration_complete_depuis_la_fiche_evenement(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create(['role' => 'admin', 'actif' => true]));
        $zone = Zone::create(['nom' => 'Abobo', 'prix_defaut' => 1500, 'actif' => true]);

        Livewire::test(CreateEvenement::class)
            ->fillForm([
                'titre' => 'Match test', 'slug' => 'match-test', 'lieu' => 'Stade', 'ville' => 'Abidjan',
                'debut_at' => now()->addWeek()->format('Y-m-d H:i'), 'statut' => 'brouillon',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(EvenementResource::getUrl('edit', ['record' => 'match-test']));

        $evt = Evenement::where('slug', 'match-test')->firstOrFail();

        Livewire::test(DepartsRelationManager::class, ['ownerRecord' => $evt, 'pageClass' => EditEvenement::class])
            ->callTableAction('create', data: [
                'depart_at' => now()->addWeek()->subHours(2)->format('Y-m-d H:i'),
                'retour_at' => now()->addWeek()->addHours(3)->format('Y-m-d H:i'),
                'places_total' => 30, 'max_par_commande' => 6, 'statut' => 'ouvert',
                'tarifs' => [['zone_id' => $zone->id, 'prix' => 1500, 'heure_ramassage' => '16:00', 'principal' => true, 'actif' => true]],
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame(1, $evt->departs()->count());
        $this->assertSame(1, $evt->departs()->first()->tarifs()->count());

        Livewire::test(EditEvenement::class, ['record' => $evt->getRouteKey()])
            ->assertSee('Au moins un départ')
            ->callAction('publier')
            ->assertHasNoActionErrors();
        $this->assertSame('publie', $evt->fresh()->statut);
    }
}
