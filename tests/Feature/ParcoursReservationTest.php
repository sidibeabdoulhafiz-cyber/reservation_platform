<?php

namespace Tests\Feature;

use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\ScanBillet;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Actions\Reservations\ValidatePayment;
use App\Enums\BilletStatus;
use App\Enums\ReservationStatus;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Setting;
use App\Models\TypeBillet;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ParcoursReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_accueil_et_connexion_sans_https(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk()->assertSee('Connexion', false);
        $this->get('/dev/payer')->assertOk()->assertSee('Simulation Wave', false);
    }

    public function test_montant_non_multiple_est_refuse(): void
    {
        Storage::fake('local');
        [$client, $depart, $zone] = $this->catalogue();

        $reservation = app(CreateReservation::class)->handle($client, [
            'type' => 'transport',
            'depart_id' => $depart->id,
            'zone_id' => $zone->id,
            'quantite' => 4,
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(SubmitPaymentProof::class)->handle(
                $reservation,
                9000,
                'T_ABCDEFGH',
                UploadedFile::fake()->image('recu.jpg'),
            );
        } catch (ValidationException $e) {
            $this->assertStringContainsString('multiple', $e->errors()['montant_declare'][0]);
            throw $e;
        }
    }

    public function test_plus_de_deux_billets_est_refuse(): void
    {
        [$client, , , $type] = $this->catalogue();

        $this->expectException(ValidationException::class);

        app(CreateReservation::class)->handle($client, [
            'type' => 'billet',
            'type_billet_id' => $type->id,
            'quantite' => 3,
        ]);
    }

    public function test_validation_emet_un_billet_par_place_puis_scan_unique(): void
    {
        Storage::fake('local');
        [$client, $depart, $zone] = $this->catalogue();
        $admin = User::factory()->create(['role' => 'admin']);
        $controleur = User::factory()->create(['role' => 'controleur']);

        $reservation = app(CreateReservation::class)->handle($client, [
            'type' => 'transport',
            'depart_id' => $depart->id,
            'zone_id' => $zone->id,
            'quantite' => 2,
        ]);

        $paiement = app(SubmitPaymentProof::class)->handle(
            $reservation,
            5000,
            'T_UNIQUE01',
            UploadedFile::fake()->image('recu.jpg'),
        );

        $payee = app(ValidatePayment::class)->handle($paiement, $admin);
        $this->assertSame(ReservationStatus::Payee, $payee->statut);
        $this->assertCount(2, $payee->billets);
        $this->assertCount(2, $payee->billets->pluck('numero')->unique());
        $this->assertCount(2, $payee->billets->pluck('siege')->unique());

        $scan = app(ScanBillet::class);
        $premier = $scan->handle($payee->billets[0]->numero, $controleur);
        $this->assertSame('valide', $premier['statut']);

        $second = $scan->handle($payee->billets[0]->numero, $controleur);
        $this->assertSame('utilise', $second['statut']);
        $this->assertSame(BilletStatus::Utilise, $payee->billets[0]->fresh()->statut);
    }

    /**
     * @return array{0: User, 1: Depart, 2: Zone, 3: TypeBillet}
     */
    private function catalogue(): array
    {
        Setting::set('site_ouvert', '1');
        Setting::set('delai_paiement_min', '30');

        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $zone = Zone::query()->create(['nom' => 'Yopougon', 'prix_defaut' => 2500, 'ordre' => 1, 'actif' => true]);
        $vehicule = Vehicule::query()->create([
            'nom' => 'Sprinter',
            'immatriculation' => 'TEST-001',
            'capacite' => 18,
            'actif' => true,
        ]);
        $evenement = Evenement::query()->create([
            'titre' => 'Concert',
            'slug' => 'concert',
            'lieu' => 'Plateau',
            'ville' => 'Abidjan',
            'debut_at' => now()->addDays(3),
            'statut' => 'publie',
        ]);
        $depart = Depart::query()->create([
            'evenement_id' => $evenement->id,
            'vehicule_id' => $vehicule->id,
            'depart_at' => now()->addDays(3)->setTime(18, 0),
            'places_total' => 18,
            'places_reservees' => 0,
            'max_par_commande' => 8,
            'statut' => 'ouvert',
        ]);
        $depart->tarifs()->create([
            'zone_id' => $zone->id,
            'prix' => 2500,
            'actif' => true,
        ]);
        $type = TypeBillet::query()->create([
            'evenement_id' => $evenement->id,
            'nom' => 'Standard',
            'prix' => 5000,
            'quota' => 50,
            'vendus' => 0,
            'max_par_commande' => 2,
            'actif' => true,
        ]);

        return [$client, $depart, $zone, $type];
    }
}
