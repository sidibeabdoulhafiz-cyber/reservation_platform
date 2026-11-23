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
}
