<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Liste de contrôle avant la mise en ligne : « php artisan cotatrans:verifier ». */
class VerifierProduction extends Command
{
    protected $signature = 'cotatrans:verifier';

    protected $description = 'Vérifie que la configuration est prête pour la production';

    private int $erreurs = 0;

    public function handle(): int
    {
        $this->info('Vérification de la configuration CotaTrans');
        $this->newLine();

        $url = (string) config('app.url');
        $this->check(app()->isProduction(), 'APP_ENV=production', 'Mets APP_ENV=production dans .env');
        $this->check(! config('app.debug'), 'APP_DEBUG=false', 'Mets APP_DEBUG=false (sinon les erreurs affichent des secrets)');
        $this->check(strlen((string) config('app.key')) > 20, 'APP_KEY définie', 'Lance php artisan key:generate');
        $this->check(str_starts_with($url, 'https://'), 'APP_URL en https ('.$url.')', 'APP_URL doit commencer par https://');
        $this->check((bool) config('session.secure'), 'Cookies de session sécurisés (SESSION_SECURE_COOKIE=true)', 'Ajoute SESSION_SECURE_COOKIE=true');
        $this->check(config('session.cookie') !== 'laravel_session' && ! str_contains((string) config('session.cookie'), 'laravel'), 'Nom du cookie de session neutre', 'Ajoute SESSION_COOKIE=cotatrans_session');
        $this->check((bool) config('cotatrans.code_connexion'), 'Code de vérification par e-mail actif', 'Retire CODE_CONNEXION=false du .env');

        $mailer = (string) config('mail.default');
        if ($mailer === 'failover') {
            $this->check(filled(config('mail.mailers.lws.host')) && ! str_contains((string) config('mail.mailers.lws.host'), '<'), 'Boîte LWS configurée (LWS_MAIL_HOST)', 'Renseignez LWS_MAIL_* dans .env');
        }
        $this->check(! in_array($mailer, ['log', 'array'], true), 'Envoi d’e-mails réel (MAIL_MAILER='.$mailer.')', 'Configure MAIL_MAILER (smtp ou brevo)');
        $from = (string) config('mail.from.address');
        $this->check($from !== '' && ! str_contains($from, 'example.com'), 'Adresse d’expédition ('.$from.')', 'Renseigne MAIL_FROM_ADDRESS');

        try {
            DB::connection()->getPdo();
            $this->check(true, 'Base de données joignable ('.config('database.default').')', '');
            $this->check(Schema::hasColumn('evenements', 'categorie'), 'Migrations à jour', 'Lance php artisan migrate --force');
            $this->check(User::where('role', 'admin')->where('actif', true)->exists(), 'Au moins un administrateur', 'Lance php artisan cotatrans:admin');
            $this->check(! User::where('email', 'like', '%@cotatrans.test')->exists(), 'Aucun compte de démonstration', 'Supprime les comptes @cotatrans.test');
            $wave = \App\Support\Wave::lienDeBase();
            $this->check($wave !== '' && ! str_contains($wave, '/dev/payer'), 'Lien de paiement Wave configuré'.($wave !== '' ? ' ('.$wave.')' : ''), 'WAVE_LINK dans .env ou Admin > Réglages > Paiement');
        } catch (\Throwable $e) {
            $this->check(false, 'Base de données joignable', $e->getMessage());
        }

        $this->check(is_dir(public_path('storage')), 'Lien public/storage (affiches)', 'cd public && ln -s ../storage/app/public storage');
        $this->check(is_writable(storage_path('logs')) && is_writable(storage_path('framework')), 'Dossier storage accessible en écriture', 'Donne les droits d’écriture à storage/ et bootstrap/cache/');
        $this->check(! file_exists(public_path('hot')), 'Pas de serveur de dev actif (public/hot)', 'Supprime public/hot');

        $this->newLine();
        if ($this->erreurs === 0) {
            $this->info('Tout est prêt pour la production.');

            return self::SUCCESS;
        }
        $this->error($this->erreurs.' point(s) à corriger avant la mise en ligne.');

        return self::FAILURE;
    }

    private function check(bool $ok, string $libelle, string $conseil): void
    {
        if ($ok) {
            $this->line('  <fg=green>[OK]</> '.$libelle);

            return;
        }
        $this->erreurs++;
        $this->line('  <fg=red>[A FAIRE]</> '.$libelle.($conseil ? ' <fg=gray>→ '.$conseil.'</>' : ''));
    }
}
