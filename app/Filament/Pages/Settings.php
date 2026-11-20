<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Wave;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Throwable;

class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Réglages';

    protected static ?string $title = 'Réglages du site';

    protected static string|\UnitEnum|null $navigationGroup = 'Plateforme';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    /** Diagnostic lisible de la configuration d'envoi des e-mails. */
    public static function etatMail(): HtmlString
    {
        $mailer = (string) config('mail.default');
        $de = (string) config('mail.from.address');
        $ok = fn (string $t) => '<span style="color:#12805c;font-weight:600">✓ '.e($t).'</span>';
        $ko = fn (string $t) => '<span style="color:#b45309;font-weight:600">⚠ '.e($t).'</span>';

        $lignes = match ($mailer) {
            'brevo' => [config('mail.mailers.brevo.key') ? $ok('Brevo (API) actif') : $ko('MAIL_MAILER=brevo mais BREVO_API_KEY est vide dans .env')],
            'smtp' => [str_contains((string) config('mail.mailers.smtp.host'), 'brevo')
                ? (config('mail.mailers.smtp.password') ? $ok('Brevo (SMTP) actif') : $ko('SMTP Brevo sans mot de passe (MAIL_PASSWORD = clé SMTP Brevo)'))
                : $ok('SMTP : '.config('mail.mailers.smtp.host'))],
            'log' => [$ko('Mode test : les e-mails sont écrits dans storage/logs/laravel.log, rien n’est envoyé. Mettez MAIL_MAILER=brevo et BREVO_API_KEY dans .env.')],
            default => [$ko('Transport : '.$mailer)],
        };
        $lignes[] = 'Expéditeur : <strong>'.e($de).'</strong>'.(str_ends_with($de, '@gmail.com') ? ' '.$ko('une adresse Gmail est souvent refusée ou classée en spam : utilisez une adresse de votre domaine validée dans Brevo') : '');
        $lignes[] = 'Envoi : '.(config('mail.en_file') ? 'en file d’attente (php artisan queue:work requis)' : 'immédiat');

        return new HtmlString(implode('<br>', $lignes));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testMail')
                ->label('Envoyer un e-mail de test')
                ->icon('heroicon-o-envelope')
                ->color('gray')
                ->schema([
                    TextInput::make('destinataire')->label('Destinataire')->email()->required()
                        ->default(fn () => auth()->user()?->email),
                ])
                ->action(function (array $data) {
                    try {
                        Mail::raw(
                            'Ceci est un e-mail de test CotaTrans. Si vous le lisez, l’envoi ('.config('mail.default').') fonctionne.',
                            fn ($m) => $m->to($data['destinataire'])->subject('Test CotaTrans · e-mails'),
                        );
                        Notification::make()->title('E-mail envoyé via « '.config('mail.default').' »')
                            ->body(config('mail.default') === 'log' ? 'Mode log : voir storage/logs/laravel.log. Passez MAIL_MAILER=brevo pour un vrai envoi.' : null)
                            ->success()->send();
                    } catch (Throwable $e) {
                        Notification::make()->title('Échec de l’envoi')->body($e->getMessage())->danger()->persistent()->send();
                    }
                }),
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'wave_link' => Setting::get('wave_link', ''),
            'wave_amount' => (string) Setting::get('wave_amount', '0') === '1',
            'delai_paiement_min' => (int) Setting::get('delai_paiement_min', 30),
            'message_global' => Setting::get('message_global', ''),
            'site_ouvert' => (string) Setting::get('site_ouvert', '1') === '1',
            'hero_image' => Setting::get('hero_image') ?: null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('wave_link')->label('Lien de paiement Wave')->required()
                    ->helperText('En production : https:// uniquement. En local, un lien http:// (simulation /dev/payer) est accepté, sans HTTPS.'),
                Toggle::make('wave_amount')->label('Ajouter le montant au lien Wave')
                    ->helperText('À activer seulement si le compte Wave Business l’accepte.'),
                TextInput::make('delai_paiement_min')->label('Délai de paiement (minutes)')
                    ->numeric()->minValue(5)->maxValue(180)->required(),
                TextInput::make('message_global')->label('Message affiché sur le site')
                    ->helperText('Facultatif. Visible en haut de toutes les pages.')->maxLength(255),
                Toggle::make('site_ouvert')->label('Réservations ouvertes'),
                Placeholder::make('etat_mail')->label('E-mails (Brevo)')
                    ->content(fn () => static::etatMail())
                    ->columnSpanFull(),
                FileUpload::make('hero_image')->label('Photo de la bannière d’accueil')
                    ->helperText('Photo paysage (ex. car de participants devant un stade ou une salle). 1920×1080 conseillé, 5 Mo max. Un dégradé bleu est ajouté à gauche pour la lisibilité du texte.')
                    ->image()->disk('public')->directory('site')->visibility('public')
                    ->maxSize(5120)->imageCropAspectRatio('16:9')->imageResizeMode('cover')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1080')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $lien = $data['wave_link'];
        if (! Wave::lienAccepte($lien)) {
            Notification::make()
                ->title(app()->environment('production')
                    ? 'Le lien Wave doit commencer par https://'
                    : 'Utilisez un lien https://, ou http://localhost en local.')
                ->danger()
                ->send();

            return;
        }

        Setting::set('wave_link', $lien);
        Setting::set('wave_amount', $data['wave_amount'] ? '1' : '0');
        Setting::set('delai_paiement_min', (string) $data['delai_paiement_min']);
        Setting::set('message_global', $data['message_global'] ?? '');
        Setting::set('site_ouvert', $data['site_ouvert'] ? '1' : '0');
        Setting::set('hero_image', $data['hero_image'] ?? '');

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
