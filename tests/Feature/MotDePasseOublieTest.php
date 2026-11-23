<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MotDePasseOublieTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcours_mot_de_passe_oublie_en_francais(): void
    {
        app()->setLocale('fr');
        Notification::fake();
        $user = User::factory()->create(['email' => 'awa@exemple.com']);

        $this->get('/forgot-password')->assertOk()->assertSee('Mot de passe oublié ?');
        $this->post('/forgot-password', ['email' => 'awa@exemple.com'])
            ->assertSessionHas('status', 'Si un compte existe avec cet e-mail, un lien de réinitialisation vient d’être envoyé.');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user) {
            $mail = $n->toMail($user);
            $this->assertSame('Réinitialisation de votre mot de passe CotaTrans', $mail->subject);
            $this->assertSame('Choisir un nouveau mot de passe', $mail->actionText);

            $this->get('/reset-password/'.$n->token.'?email='.urlencode($user->email))
                ->assertOk()->assertSee('Nouveau mot de passe');

            return true;
        });
    }
}
