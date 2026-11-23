<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceHttps();
        }

        // Mot de passe : 8 caractères minimum, avec au moins une lettre et un chiffre.
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // MAIL_MAILER=brevo : envoi par l'API Brevo (voir config/mail.php).
        Mail::extend('brevo', fn (array $config) => new BrevoTransport(
            (string) ($config['key'] ?? config('services.brevo.key', '')),
        ));
    }
}
