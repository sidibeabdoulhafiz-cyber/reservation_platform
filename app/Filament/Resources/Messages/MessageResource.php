<?php

namespace App\Filament\Resources\Messages;

use App\Filament\Resources\Messages\Pages\ManageMessages;
use App\Models\Message;
use BackedEnum;
use Filament\Actions\Action;
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
                TextColumn::make('created_at')->label('Reçu')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(['organisateur' => 'Organisateurs', 'contact' => 'Contact']),
                SelectFilter::make('statut')->options(self::STATUTS),
            ])
            ->recordActions([
                Action::make('repondre')->label('Répondre')->icon('heroicon-o-envelope')->color('gray')
                    ->url(fn (Message $record) => 'mailto:'.$record->email.'?subject='.rawurlencode('Re : '.($record->evenement ?: $record->sujet ?: 'votre message à CotaTrans'))),
                EditAction::make()->label('Ouvrir')
                    ->mutateRecordDataUsing(function (array $data, Message $record) {
                        if ($record->statut === 'nouveau') {
                            $record->update(['statut' => 'en_cours']);
                            $data['statut'] = 'en_cours';
                        }

                        return $data;
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageMessages::route('/')];
    }
}
