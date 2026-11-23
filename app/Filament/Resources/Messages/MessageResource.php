<?php

namespace App\Filament\Resources\Messages;

use App\Actions\Messages\CreerEvenementBrouillon;
use App\Actions\Messages\RepondreMessage;
use App\Filament\Resources\Evenements\EvenementResource;
use App\Filament\Resources\Messages\Pages\ManageMessages;
use App\Models\Message;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'Messages & demandes';

    protected static ?string $modelLabel = 'message';

    protected static ?string $pluralModelLabel = 'messages';

    protected static string|\UnitEnum|null $navigationGroup = 'Plateforme';

    protected static ?int $navigationSort = 1;

    public const STATUTS = [
        'nouveau' => 'Nouveau',
        'en_cours' => 'En cours',
        'traite' => 'Traité',
        'refuse' => 'Refusé',
    ];

    public static function getNavigationBadge(): ?string
    {
        $n = Message::where('statut', 'nouveau')->count();

        return $n > 0 ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Message reçu')
                ->columns(2)
                ->schema([
                    TextEntry::make('type')->label('Type')->formatStateUsing(fn ($state) => $state === 'organisateur' ? 'Demande organisateur' : 'Contact'),
                    TextEntry::make('created_at')->label('Reçu le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('nom'),
                    TextEntry::make('organisation')->placeholder('—'),
                    TextEntry::make('email')->label('E-mail')->copyable(),
                    TextEntry::make('tel')->label('Téléphone')->placeholder('—')->copyable(),
                    TextEntry::make('evenement')->label('Événement')->placeholder('—')->visible(fn (?Message $record) => $record?->estOrganisateur()),
                    TextEntry::make('date_evenement')->label('Date')->date('d/m/Y')->placeholder('—')->visible(fn (?Message $record) => $record?->estOrganisateur()),
                    TextEntry::make('lieu')->placeholder('—')->visible(fn (?Message $record) => $record?->estOrganisateur()),
                    TextEntry::make('participants')->label('Participants')->placeholder('—')->visible(fn (?Message $record) => $record?->estOrganisateur()),
                    TextEntry::make('sujet')->placeholder('—')->visible(fn (?Message $record) => ! $record?->estOrganisateur()),
                    TextEntry::make('message')->placeholder('—')->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make('Réponses envoyées')
                ->visible(fn (?Message $record) => filled($record?->reponses))
                ->schema([
                    TextEntry::make('historique')->hiddenLabel()->html()
                        ->state(fn (?Message $record) => collect($record?->reponses ?? [])->reverse()->map(fn ($r) => '<div style="padding:10px 0;border-bottom:1px solid rgba(0,0,0,.07)"><b>'.e($r['sujet']).'</b><br><small style="opacity:.7">'.e(\Illuminate\Support\Carbon::parse($r['le'])->format('d/m/Y H:i')).($r['par'] ? ' · '.e($r['par']) : '').'</small><div style="margin-top:6px;white-space:pre-line">'.e($r['texte']).'</div></div>')->implode('')),
                ])
                ->columnSpanFull(),
            Section::make('Suivi')
                ->schema([
                    Select::make('statut')->options(self::STATUTS)->required(),
                    Textarea::make('note_interne')->label('Note interne')->rows(3)
                        ->helperText('Visible uniquement par l’équipe (négociation, prix proposé…).'),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('statut')->badge()
                    ->formatStateUsing(fn (string $state) => self::STATUTS[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'nouveau' => 'warning', 'en_cours' => 'info', 'traite' => 'success', default => 'gray'
                    }),
                TextColumn::make('type')->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'organisateur' ? 'Organisateur' : 'Contact')
                    ->color(fn (string $state) => $state === 'organisateur' ? 'primary' : 'gray'),
                TextColumn::make('nom')->searchable()->description(fn (Message $record) => $record->email),
                TextColumn::make('objet')->label('Objet')
                    ->state(fn (Message $record) => $record->estOrganisateur() ? $record->evenement : $record->sujet)
                    ->description(fn (Message $record) => $record->estOrganisateur()
                        ? trim(($record->date_evenement?->format('d/m/Y') ?? '').' '.($record->lieu ?? ''))
                        : null)
                    ->wrap(),
                TextColumn::make('repondu_at')->label('Réponse')->since()->placeholder('Pas encore')
                    ->color(fn ($state) => $state ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Reçu')->since()->sortable(),
            ])
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('type')->options(['organisateur' => 'Organisateurs', 'contact' => 'Contact']),
                SelectFilter::make('statut')->options(self::STATUTS),
            ])
            ->recordActions([
                self::actionRepondre(),
                self::actionBrouillon(),
                ActionGroup::make([
                    EditAction::make()->label('Ouvrir / statut')
                        ->mutateRecordDataUsing(function (array $data, Message $record) {
                            if ($record->statut === 'nouveau') {
                                $record->update(['statut' => 'en_cours']);
                                $data['statut'] = 'en_cours';
                            }

                            return $data;
                        }),
                    DeleteAction::make(),
                ])->tooltip('Plus d’actions'),
            ]);
    }

    public static function actionRepondre(): Action
    {
        return Action::make('repondre')->label('Répondre')->icon('heroicon-o-paper-airplane')->color('primary')
            ->modalHeading(fn (Message $record) => 'Répondre à '.$record->nom)
            ->modalDescription(fn (Message $record) => $record->email.' · la réponse part par e-mail et reste dans l’historique.')
            ->modalSubmitActionLabel('Envoyer la réponse')
            ->modalWidth('2xl')
            ->fillForm(fn (Message $record) => [
                'sujet' => 'Re : '.$record->objet(),
                'texte' => RepondreMessage::modele($record),
                'statut' => $record->statut === 'nouveau' ? 'en_cours' : $record->statut,
            ])
            ->schema([
                TextInput::make('sujet')->label('Objet')->required()->maxLength(150),
                Textarea::make('texte')->label('Message')->required()->rows(12)->maxLength(5000),
                Select::make('statut')->label('Statut après envoi')->options(self::STATUTS)->required(),
            ])
            ->action(function (array $data, Message $record, RepondreMessage $repondre) {
                try {
                    $repondre->handle($record, $data['sujet'], $data['texte'], $data['statut'], Auth::user());
                    Notification::make()->title('Réponse envoyée à '.$record->email)->success()->send();
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()->title('L’e-mail n’a pas pu partir')->body('Vérifie les réglages e-mail puis réessaie.')->danger()->send();
                }
            });
    }

    public static function actionBrouillon(): Action
    {
        return Action::make('brouillon')
            ->label(fn (Message $record) => $record->evenement_id ? 'Voir l’événement' : 'Créer l’événement')
            ->icon(fn (Message $record) => $record->evenement_id ? 'heroicon-o-arrow-top-right-on-square' : 'heroicon-o-document-plus')
            ->color('info')
            ->requiresConfirmation(fn (Message $record) => ! $record->evenement_id)
            ->modalHeading('Créer l’événement en brouillon ?')
            ->modalDescription('Un brouillon est créé avec les infos de la demande (nom, date, lieu, description). Un admin le complète ensuite : affiche, départs, tarifs, puis publication.')
            ->modalSubmitActionLabel('Créer le brouillon')
            ->action(function (Message $record, CreerEvenementBrouillon $creer, Component $livewire) {
                $existait = (bool) $record->evenement_id;
                $evt = $creer->handle($record);
                if (! $existait) {
                    Notification::make()->title('Brouillon créé : '.$evt->titre)->body('Complète-le puis publie-le.')->success()->send();
                }
                $livewire->redirect(EvenementResource::getUrl('edit', ['record' => $evt]));
            });
    }

    public static function getPages(): array
    {
        return ['index' => ManageMessages::route('/')];
    }
}
