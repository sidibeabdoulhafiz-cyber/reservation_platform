<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Wave;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Mail;
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
                FileUpload::make('hero_image')->label('Photo de la bannière d’accueil')
                    ->helperText('Photo paysage (ex. car de participants devant un stade ou une salle). 1920×1080 conseillé, 5 Mo max. Un dégradé bleu est ajouté à gauche pour la lisibilité du texte.')
                    ->image()->disk('public')->directory('site')->visibility('public')
                    ->maxSize(5120)->imageEditor()->imageEditorAspectRatioOptions(['16:9', '21:9'])
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
