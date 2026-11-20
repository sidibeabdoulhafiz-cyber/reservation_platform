<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Livewire\Pages\Contact;
use App\Livewire\Pages\Organisateurs;
use App\Livewire\Reservations\Create;
use App\Mail\AccuseReception;
use App\Mail\NouveauMessage;
use App\Models\Depart;
use App\Models\Evenement;
use App\Models\Message;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vehicule;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class SiteVitrineTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_organisateur_propose_son_evenement(): void
    {
        Mail::fake();
        Setting::set('email_reception', 'equipe@cotatrans.ci');

        Livewire::test(Organisateurs::class)
            ->set('nom', 'Koffi Yao')->set('email', 'koffi@exemple.com')->set('tel', '07 00 00 00 01')
            ->set('evenement', 'Festival des grillades')->set('date_evenement', now()->addMonth()->toDateString())
            ->set('lieu', 'Stade FHB')->set('participants', '3000')->set('message', 'Public surtout à Yopougon.')
            ->call('envoyer')
            ->assertHasNoErrors()
            ->assertSet('envoye', true)
            ->assertSee('Demande envoyée');

        $m = Message::firstOrFail();
        $this->assertSame('organisateur', $m->type);
        $this->assertSame(3000, $m->participants);
        Mail::assertSent(NouveauMessage::class, fn ($mail) => $mail->hasTo('equipe@cotatrans.ci') && $mail->hasReplyTo('koffi@exemple.com'));
        Mail::assertSent(AccuseReception::class, fn ($mail) => $mail->hasTo('koffi@exemple.com'));
    }

    public function test_formulaire_contact_et_anti_robot(): void
    {
        Mail::fake();

        Livewire::test(Contact::class)
            ->set('nom', 'Awa')->set('email', 'awa@exemple.com')->set('sujet', 'Ma réservation')
            ->set('message', 'Bonjour, une question sur mon départ.')
            ->call('envoyer')->assertHasNoErrors()->assertSee('Message envoyé');
        $this->assertSame(1, Message::where('type', 'contact')->count());

        // Robot : champ piège rempli → rien n'est enregistré ni envoyé.
        Mail::fake();
        Livewire::test(Contact::class)
            ->set('nom', 'Bot')->set('email', 'bot@spam.com')->set('sujet', 'Promo')->set('message', 'Achetez maintenant !!!')
            ->set('site_web', 'http://spam')
            ->call('envoyer')->assertSet('envoye', true);
        $this->assertSame(1, Message::count());
        Mail::assertNothingSent();

        Livewire::test(Contact::class)->call('envoyer')->assertHasErrors(['nom', 'email', 'sujet', 'message']);
    }

    public function test_reseaux_sociaux_dans_le_pied_de_page_et_reglages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin', 'actif' => true]));
        Setting::set('wave_link', 'https://pay.wave.com/m/test');

        Livewire::test(Settings::class)
            ->set('data.reseau_facebook', 'https://facebook.com/cotatrans')
            ->set('data.reseau_tiktok', 'https://tiktok.com/@cotatrans')
            ->set('data.email_reception', 'equipe@cotatrans.ci')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('https://facebook.com/cotatrans', Setting::get('reseau_facebook'));
        $this->get('/')->assertOk()
            ->assertSee('https://facebook.com/cotatrans', false)
            ->assertSee('CotaTrans sur TikTok', false)
            ->assertDontSee('CotaTrans sur Instagram', false);
    }

    public function test_bandeau_cookies_seulement_si_mesure_activee(): void
    {
        $this->get('/')->assertDontSee('ct_cookies', false);
        Setting::set('ga4_id', 'G-TEST12345');
        $this->get('/')->assertSee('ct_cookies', false)->assertSee('G-TEST12345', false)
            ->assertDontSee('<script async src="https://www.googletagmanager.com', false);
        $this->get('/confidentialite')->assertOk()->assertSee('Gérer mes cookies');
    }

    public function test_referencement_meta_sitemap_robots_et_donnees_structurees(): void
    {
        $evt = Evenement::create(['titre' => 'Concert Himra', 'slug' => 'concert-himra', 'lieu' => 'Ebimpé', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(10), 'statut' => 'publie']);
        Evenement::create(['titre' => 'Brouillon secret', 'slug' => 'brouillon', 'lieu' => 'X', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(10), 'statut' => 'brouillon']);

        $this->get('/')->assertOk()
            ->assertSee('<link rel="canonical"', false)
            ->assertSee('property="og:image"', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('Transport aller-retour pour vos événements et sorties à Abidjan', false)
            ->assertSee('index, follow', false);

        $this->get(route('evenements.show', $evt))->assertOk()
            ->assertSee('<title>Transport Concert Himra', false)
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"addressCountry":"CI"', false);

        $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('evenements.show', $evt), false)
            ->assertDontSee('brouillon', false);

        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: '.route('sitemap'), false)->assertSee('Disallow: /admin', false);
        $this->get('/login')->assertSee('noindex, nofollow', false);
    }

    public function test_visiteur_sans_compte_le_bloc_inscription_est_mis_en_avant(): void
    {
        $evt = Evenement::create(['titre' => 'E', 'slug' => 'e', 'lieu' => 'L', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(3), 'statut' => 'publie']);
        $z = Zone::create(['nom' => 'Cocody', 'prix_defaut' => 2000, 'actif' => true]);
        $v = Vehicule::create(['nom' => 'Car', 'immatriculation' => 'V', 'capacite' => 20, 'actif' => true]);
        $d = Depart::create(['evenement_id' => $evt->id, 'vehicule_id' => $v->id, 'depart_at' => now()->addDays(3), 'places_total' => 20, 'places_reservees' => 0, 'max_par_commande' => 8, 'statut' => 'ouvert']);
        $d->tarifs()->create(['zone_id' => $z->id, 'ordre' => 1, 'principal' => true, 'prix' => 2000, 'actif' => true]);
        Setting::set('site_ouvert', '1');

        Livewire::test(Create::class, ['evenement' => $evt])
            ->assertSee('Dernière étape : ton compte')
            ->assertSee('Créer mon compte et réserver')
            ->call('reserve')
            ->assertHasErrors(['nom', 'tel', 'email', 'password'])
            ->assertDispatched('attirer-inscription');
    }

    public function test_bouton_whatsapp_flottant_selon_le_reglage(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="wa-float', false);

        Setting::set('whatsapp_numero', '+225 07 00 11 22 33');
        $this->get('/')->assertOk()
            ->assertSee('class="wa-float', false)
            ->assertSee('https://wa.me/2250700112233', false);
    }

    public function test_reglage_numero_whatsapp_dans_l_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'actif' => true]);
        $this->actingAs($admin);

        Livewire::test(Settings::class)
            ->set('data.whatsapp_numero', 'pas un numéro')
            ->call('save')
            ->assertHasErrors(['data.whatsapp_numero']);

        Livewire::test(Settings::class)
            ->set('data.wave_link', 'https://pay.wave.com/m/M_test/c/ci/')
            ->set('data.whatsapp_numero', '+225 07 00 11 22 33')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('+225 07 00 11 22 33', Setting::get('whatsapp_numero'));
    }

    public function test_partage_d_un_evenement_whatsapp_et_facebook(): void
    {
        $evt = Evenement::create(['titre' => 'Concert Himra', 'slug' => 'concert-himra', 'lieu' => 'Ebimpé', 'ville' => 'Abidjan', 'debut_at' => now()->addDays(10), 'statut' => 'publie']);
        $url = rawurlencode(route('evenements.show', $evt));

        $this->get(route('evenements.show', $evt))->assertOk()
            ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.$url, false)
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee($url, false);
    }
}
