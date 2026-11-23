<?php

namespace Tests\Feature;

use App\Actions\Messages\CreerEvenementBrouillon;
use App\Actions\Messages\RepondreMessage;
use App\Actions\Reservations\CreateReservation;
use App\Actions\Reservations\SubmitPaymentProof;
use App\Actions\Reservations\ValidatePayment;
use App\Livewire\Evenements\Catalogue;
use App\Mail\CodeVerification;
use App\Mail\ReponseMessage;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use App\Support\CodeConnexion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class NouveautesNov24Test extends TestCase
{
    use RefreshDatabase;

    private function message(array $attrs = []): Message
    {
        return Message::create(array_merge([
            'type' => 'organisateur', 'nom' => 'Koffi Yao', 'email' => 'koffi@exemple.ci', 'organisation' => 'Yao Events',
            'evenement' => 'Concert Live Bassam', 'date_evenement' => now()->addDays(40)->toDateString(), 'lieu' => 'Plage de Bassam',
            'participants' => 800, 'message' => 'Nous attendons beaucoup de monde.', 'statut' => 'nouveau',
        ], $attrs));
    }

    public function test_repondre_a_une_demande_envoie_un_mail_et_garde_l_historique(): void
    {
        Mail::fake();
        $m = $this->message();
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Hafiz Admin']);

        app(RepondreMessage::class)->handle($m, 'Re : Concert Live Bassam', "Bonjour Koffi,\nVoici notre offre.", 'en_cours', $admin);

        Mail::assertSent(ReponseMessage::class, fn ($mail) => $mail->hasTo('koffi@exemple.ci') && $mail->sujet === 'Re : Concert Live Bassam');
        $m->refresh();
        $this->assertSame('en_cours', $m->statut);
        $this->assertNotNull($m->repondu_at);
        $this->assertCount(1, $m->reponses);
        $this->assertSame('Hafiz Admin', $m->reponses[0]['par']);
        $this->assertStringContainsString('Concert Live Bassam', RepondreMessage::modele($m));
    }

    public function test_creer_l_evenement_en_brouillon_depuis_une_demande(): void
    {
        $m = $this->message();
        Evenement::create(['titre' => 'X', 'slug' => 'concert-live-bassam', 'lieu' => 'L', 'ville' => 'Abidjan', 'debut_at' => now()->addDay(), 'statut' => 'publie']);

        $evt = app(CreerEvenementBrouillon::class)->handle($m);

        $this->assertSame('brouillon', $evt->statut);
        $this->assertSame('Concert Live Bassam', $evt->titre);
        $this->assertSame('concert-live-bassam-2', $evt->slug);
        $this->assertSame('concert', $evt->categorie);
        $this->assertSame('Plage de Bassam', $evt->lieu);
        $this->assertSame(20, $evt->debut_at->hour);
        $this->assertStringContainsString('Yao Events', $evt->description);
        $this->assertSame($evt->id, $m->fresh()->evenement_id);
        $this->assertSame('en_cours', $m->fresh()->statut);

        // Deuxième clic : pas de doublon.
        $this->assertSame($evt->id, app(CreerEvenementBrouillon::class)->handle($m->fresh())->id);
        $this->assertSame(2, Evenement::count());
        // Le brouillon n'est pas visible sur le site.
        $this->get('/evenements/'.$evt->slug)->assertNotFound();
    }

    public function test_code_par_e_mail_a_la_connexion_et_appareil_de_confiance(): void
    {
        config(['cotatrans.code_connexion' => true]);
        Mail::fake();
        $user = User::factory()->create(['role' => 'client', 'actif' => true, 'email_verified_at' => null]);

        $this->actingAs($user)->get('/mes-reservations')->assertRedirect(route('code.show'));

        $this->get(route('code.show'))->assertOk()->assertSee($user->email);
        $code = null;
        Mail::assertSent(CodeVerification::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $faux = $code === '000000' ? '111111' : '000000';
        $this->from(route('code.show'))->post(route('code.store'), ['code' => $faux])->assertSessionHasErrors('code');

        $rep = $this->post(route('code.store'), ['code' => $code, 'confiance' => '1']);
        $rep->assertRedirect('/mes-reservations');
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/mes-reservations')->assertOk();

        // Nouvelle session sur le même appareil : le cookie de confiance évite un nouveau code.
        $cookie = collect($rep->headers->getCookies())->firstWhere(fn ($c) => $c->getName() === CodeConnexion::COOKIE);
        $this->assertNotNull($cookie);
        $valeur = \Illuminate\Cookie\CookieValuePrefix::remove(decrypt($cookie->getValue(), false));
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/mes-reservations')->assertRedirect(route('code.show'));
        $this->flushSession();
        $this->actingAs($user->fresh())->withCookie(CodeConnexion::COOKIE, $valeur)
            ->get('/mes-reservations')->assertOk();
    }

    public function test_le_code_bloque_l_admin_tant_qu_il_n_est_pas_saisi(): void
    {
        config(['cotatrans.code_connexion' => true]);
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'actif' => true]);

        $this->actingAs($admin)->get('/admin')->assertRedirect(route('code.show'));
    }

    public function test_compte_desactive_est_deconnecte(): void
    {
        $user = User::factory()->create(['role' => 'client', 'actif' => false]);
        $this->actingAs($user)->get('/mes-reservations')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_filtre_par_categorie(): void
    {
        Evenement::create(['titre' => 'Match ASEC', 'slug' => 'match-asec', 'categorie' => 'match', 'lieu' => 'Stade', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(3), 'statut' => 'publie']);
        Evenement::create(['titre' => 'Concert Himra', 'slug' => 'concert-himra', 'categorie' => 'concert', 'lieu' => 'Palais', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(4), 'statut' => 'publie']);

        Livewire::test(Catalogue::class)
            ->assertSee('Matchs')->assertSee('Concerts')->assertDontSee('Festivals')
            ->set('cat', 'match')
            ->assertSee('Match ASEC')->assertDontSee('Concert Himra')
            ->set('cat', 'nimporte')
            ->assertSet('cat', '')
            ->assertSee('Concert Himra');
    }

    public function test_bandeau_d_annonce_et_gestion_des_cookies_sans_mesure(): void
    {
        Setting::set('message_global', 'Places limitées pour le Femua !');
        Setting::set('message_global_style', 'promo');

        $this->get('/confidentialite')->assertOk()
            ->assertSee('annonce annonce-promo', false)
            ->assertSee('Places limitées pour le Femua !')
            ->assertSee('class="cookie"', false)
            ->assertSee('n’utilise actuellement que des cookies indispensables', false)
            ->assertDontSee('Brevo')
            ->assertDontSee('Google Analytics');
    }

    public function test_agenda_ics_et_itineraire_pour_un_billet_paye(): void
    {
        Storage::fake('local');
        Setting::set('site_ouvert', '1');
        $client = User::factory()->create(['role' => 'client', 'actif' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $z = Zone::create(['nom' => 'Cocody', 'prix_defaut' => 3000, 'actif' => true]);
        $v = Vehicule::create(['nom' => 'Car 1', 'immatriculation' => 'AB-1', 'capacite' => 30, 'actif' => true]);
        $evt = Evenement::create(['titre' => 'Femua', 'slug' => 'femua', 'lieu' => 'Anoumabo', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(6), 'statut' => 'publie']);
        $d = Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays(6)->setTime(18, 0), 'retour_at' => now()->addDays(7)->setTime(1, 0), 'places_total' => 30, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z->id, 'ordre' => 1, 'principal' => true, 'prix' => 3000, 'point_rdv' => 'Carrefour de la Vie', 'heure_ramassage' => '17:30', 'actif' => true]);

        $r = app(CreateReservation::class)->handle($client, ['type' => 'transport', 'depart_id' => $d->id, 'zone_id' => $z->id, 'quantite' => 1]);
        $p = app(SubmitPaymentProof::class)->handle($r, 3000, 'T_ICS00001', UploadedFile::fake()->image('r.jpg'));
        app(ValidatePayment::class)->handle($p, $admin);

        $this->actingAs($client)->get(route('reservations.show', $r))->assertOk()
            ->assertSee('Ajouter à mon agenda')->assertSee('Carrefour%20de%20la%20Vie', false);

        $ics = $this->get(route('reservations.agenda', $r))->assertOk()->assertHeader('content-type', 'text/calendar; charset=utf-8')->getContent();
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('Carrefour de la Vie', $ics);
        $this->assertStringContainsString('TRIGGER:-PT1H', $ics);

        $autre = User::factory()->create(['role' => 'client']);
        $this->actingAs($autre)->get(route('reservations.agenda', $r))->assertForbidden();
    }

    public function test_page_404_aux_couleurs_et_en_tetes_de_securite(): void
    {
        $this->get('/evenements/n-existe-pas')->assertNotFound()->assertSee('Page introuvable');

        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertSee('manifest.webmanifest', false);
    }

    public function test_tableau_de_bord_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'actif' => true]);
        $this->message();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Nouvel événement');
    }
}
