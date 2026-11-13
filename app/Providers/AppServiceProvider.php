<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

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

        // MAIL_MAILER=brevo : envoi par l'API Brevo (voir config/mail.php).
        Mail::extend('brevo', fn (array $config) => new BrevoTransport(
            (string) ($config['key'] ?? config('services.brevo.key', '')),
        ));
    }
}
