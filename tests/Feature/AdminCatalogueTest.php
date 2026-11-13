<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Filament\Resources\Departs\Pages\ManageDeparts;
use App\Filament\Resources\Evenements\Pages\ManageEvenements;
use App\Models\Evenement;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'admin', 'actif' => true]));
    }

    public function test_admin_cree_un_evenement_avec_affiche_et_groupe_whatsapp(): void
    {
        Storage::fake('public');

        Livewire::test(ManageEvenements::class)
            ->callAction('create', [
                'image_path' => UploadedFile::fake()->image('affiche.jpg', 1600, 900),
                'titre' => 'Festival Test',
                'slug' => 'festival-test',
                'accroche' => 'Le festival de l’année',
                'lieu' => 'Stade',
                'ville' => 'Abidjan',
                'debut_at' => now()->addWeek()->format('Y-m-d H:i'),
                'statut' => 'publie',
                'lien_whatsapp' => 'https://chat.whatsapp.com/abc',
            ])
            ->assertHasNoActionErrors();

        $evt = Evenement::where('slug', 'festival-test')->firstOrFail();
        $this->assertNotNull($evt->image_path);
        Storage::disk('public')->assertExists($evt->image_path);
        $this->assertSame('https://chat.whatsapp.com/abc', $evt->lien_whatsapp);
        $this->assertStringContainsString('/storage/evenements/', $evt->imageUrl());
    }

    public function test_admin_cree_un_depart_avec_arrets_ordonnes(): void
    {
        $evt = Evenement::create(['titre' => 'E', 'slug' => 'e', 'lieu' => 'L', 'ville' => 'Abidjan', 'debut_at' => now()->addWeek(), 'statut' => 'publie']);
        $a = Zone::create(['nom' => 'Abobo', 'prix_defaut' => 2000, 'actif' => true]);
        $b = Zone::create(['nom' => 'Adjamé', 'prix_defaut' => 1500, 'actif' => true]);

        Livewire::test(ManageDeparts::class)
            ->callAction('create', [
                'evenement_id' => $evt->id,
                'depart_at' => now()->addWeek()->setTime(17, 0)->format('Y-m-d H:i'),
                'places_total' => 30,
                'max_par_commande' => 6,
                'statut' => 'ouvert',
                'tarifs' => [
                    ['zone_id' => $a->id, 'prix' => 2000, 'heure_ramassage' => '16:00', 'principal' => true, 'actif' => true],
                    ['zone_id' => $b->id, 'prix' => 1500, 'heure_ramassage' => '16:30', 'principal' => false, 'actif' => true],
                ],
            ])
            ->assertHasNoActionErrors();

        $arrets = $evt->departs()->firstOrFail()->tarifs;
        $this->assertSame(['Abobo', 'Adjamé'], $arrets->map(fn ($t) => $t->zone->nom)->all());
        $this->assertSame([1, 2], $arrets->pluck('ordre')->all());
        $this->assertTrue($arrets[0]->principal);
    }

    public function test_reglages_envoient_un_email_de_test(): void
    {
        Mail::fake();

        Livewire::test(Settings::class)
            ->callAction('testMail', ['destinataire' => 'moi@exemple.com'])
            ->assertHasNoActionErrors();
    }
}
