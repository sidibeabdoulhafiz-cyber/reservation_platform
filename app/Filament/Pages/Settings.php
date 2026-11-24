<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Wave;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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
            'wave_link' => Setting::get('wave_link', '') ?: config('cotatrans.wave_link', ''),
            'wave_amount' => Wave::montantActif(),
            'delai_paiement_min' => (int) Setting::get('delai_paiement_min', 30),
            'message_global' => Setting::get('message_global', ''),
            'site_ouvert' => (string) Setting::get('site_ouvert', '1') === '1',
            'hero_image' => Setting::get('hero_image') ?: null,
            ...collect([...array_keys(self::RESEAUX), ...self::TEXTES])->mapWithKeys(fn ($k) => [$k => Setting::get($k, '')])->all(),
            ...collect(self::MAILS_FACULTATIFS)->mapWithKeys(fn ($lib, $k) => [$k => (string) Setting::get($k, '1') === '1'])->all(),
        ]);
    }

    /** E-mails qu'on peut couper pour économiser le quota d'envoi (clé => libellé). */
    public const MAILS_FACULTATIFS = [
        'mail_reservation_creee' => 'Réservation enregistrée (lien pour payer)',
        'mail_paiement_recu' => 'Preuve de paiement reçue',
        'mail_rappel_veille' => 'Rappel la veille du départ',
        'mail_rappel_depart' => 'Rappel 2 h avant le départ',
        'mail_accuse_reception' => 'Accusé de réception des formulaires',
    ];

    /** Réglages texte simples enregistrés tels quels (clé => libellé). */
    public const RESEAUX = [
        'reseau_facebook' => 'Facebook',
        'reseau_instagram' => 'Instagram',
        'reseau_tiktok' => 'TikTok',
        'reseau_whatsapp' => 'WhatsApp (chaîne, groupe ou wa.me)',
        'reseau_youtube' => 'YouTube',
        'reseau_x' => 'X (Twitter)',
        'reseau_linkedin' => 'LinkedIn',
    ];

    public const TEXTES = ['email_reception', 'ga4_id', 'meta_pixel_id', 'google_site_verification', 'contact_tel', 'whatsapp_numero', 'whatsapp_message', 'message_global_style', 'message_global_lien'];

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Paiement et réservations')->columns(2)->schema([
                    TextInput::make('wave_link')->label('Lien de paiement Wave Business')->required()->columnSpanFull()
                        ->placeholder('https://pay.wave.com/m/M_ci_…/c/ci/')
                        ->helperText('Collez votre lien marchand Wave. Si vous le copiez avec « ?amount=… », ce montant est retiré : chaque client voit automatiquement le prix exact de sa réservation.'),
                    Toggle::make('wave_amount')->label('Pré-remplir le montant dans Wave')
                        ->helperText('Recommandé : le client ouvre Wave avec le bon montant déjà saisi.'),
                    Toggle::make('site_ouvert')->label('Réservations ouvertes'),
                    TextInput::make('delai_paiement_min')->label('Délai de paiement (minutes)')
                        ->numeric()->minValue(5)->maxValue(180)->required(),
                ])->columnSpanFull(),

                Section::make('Site')->schema([
                    TextInput::make('message_global')->label('Bandeau d’annonce')
                        ->helperText('Facultatif. Bandeau en haut de toutes les pages. Le visiteur peut le fermer ; il réapparaît si tu changes le texte.')->maxLength(180)
                        ->placeholder('Ex. Réservations ouvertes pour le Festival des Grillades ! Places limitées.'),
                    Select::make('message_global_style')->label('Style du bandeau')->options([
                        'info' => 'Information (bleu)',
                        'promo' => 'Bonne nouvelle / promo (orange)',
                        'alerte' => 'Important (rouge)',
                    ])->default('info'),
                    TextInput::make('message_global_lien')->label('Lien du bandeau (facultatif)')->url()
                        ->placeholder('https://cotatrans.ci/evenements/…')->helperText('Ajoute un bouton « En savoir plus ».'),
                    FileUpload::make('hero_image')->label('Photo de la bannière d’accueil')
                        ->helperText('Photo paysage (ex. car de participants devant un stade ou une salle). 1920×1080 conseillé, 5 Mo max. Un dégradé bleu est ajouté à gauche pour la lisibilité du texte.')
                        ->image()->disk('public')->directory('site')->visibility('public')
                        ->maxSize(5120)->imageCropAspectRatio('16:9')->imageResizeMode('cover')->imageResizeTargetWidth('1920')->imageResizeTargetHeight('1080')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                ])->columnSpanFull(),

                Section::make('E-mails (Brevo) et contact')->columns(2)->schema([
                    Placeholder::make('etat_mail')->label('État de l’envoi')
                        ->content(fn () => static::etatMail())
                        ->columnSpanFull(),
                    TextInput::make('email_reception')->label('E-mail qui reçoit les messages du site')->email()
                        ->placeholder(config('mail.from.address'))
                        ->helperText('Demandes d’organisateurs et formulaire « Nous contacter ». Vide = adresse d’expédition.'),
                    TextInput::make('contact_tel')->label('Téléphone affiché (pied de page)')->tel()->placeholder('+225 07 00 00 00 00'),
                    TextInput::make('whatsapp_numero')->label('Numéro WhatsApp de contact')->tel()
                        ->placeholder('+225 07 00 00 00 00')
                        ->regex('/^\+?[\d\s.-]{8,20}$/')
                        ->helperText('Affiche le bouton WhatsApp flottant en bas à droite du site. Indicatif pays obligatoire (+225).'),
                    TextInput::make('whatsapp_message')->label('Message pré-rempli WhatsApp')
                        ->placeholder('Bonjour CotaTrans, j’ai une question…')->maxLength(200),
                ])->columnSpanFull(),

                Section::make('E-mails envoyés aux clients')
                    ->description('Toujours envoyés : code de vérification, billets, reçu refusé, mot de passe oublié. Les autres peuvent être coupés pour économiser le quota journalier de Brevo (300 e-mails/jour en offre gratuite).')
                    ->columns(2)
                    ->schema(collect(self::MAILS_FACULTATIFS)->map(fn ($lib, $k) => Toggle::make($k)->label($lib))->values()->all())
                    ->columnSpanFull(),

                Section::make('Réseaux sociaux')
                    ->description('Les icônes apparaissent dans le pied de page pour chaque lien renseigné.')
                    ->columns(2)
                    ->schema(collect(self::RESEAUX)->map(fn ($lib, $cle) => TextInput::make($cle)->label($lib)->url()->placeholder('https://…'))->values()->all())
                    ->columnSpanFull(),

                Section::make('Cookies et mesure d’audience')
                    ->description('Les outils de mesure ne sont chargés qu’après l’accord du visiteur (bannière cookies). Laisser vide pour ne rien charger.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('ga4_id')->label('Google Analytics 4 (ID de mesure)')->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Z0-9]{4,15}$/')->helperText('Statistiques de visites et de réservations.'),
                        TextInput::make('meta_pixel_id')->label('Meta Pixel (Facebook / Instagram)')->placeholder('123456789012345')
                            ->regex('/^\d{8,20}$/')->helperText('Mesure et ciblage de vos publicités Facebook / Instagram.'),
                        TextInput::make('google_site_verification')->label('Google Search Console (code de vérification)')
                            ->helperText('Contenu de la balise « google-site-verification » (sans cookie, pour le référencement).')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $lien = Wave::nettoyer((string) $data['wave_link']);
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
        foreach ([...array_keys(self::RESEAUX), ...self::TEXTES] as $cle) {
            Setting::set($cle, trim((string) ($data[$cle] ?? '')));
        }
        foreach (array_keys(self::MAILS_FACULTATIFS) as $cle) {
            Setting::set($cle, ! empty($data[$cle]) ? '1' : '0');
        }

        Notification::make()->title('Réglages enregistrés')->success()->send();
    }
}
