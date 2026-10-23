<?php

namespace App\Filament\Resources\PublicacionExistencias;

use App\Filament\Resources\PublicacionExistencias\Pages\CreatePublicacionExistencia;
use App\Filament\Resources\PublicacionExistencias\Pages\EditPublicacionExistencia;
use App\Filament\Resources\PublicacionExistencias\Pages\ListPublicacionExistencias;
use App\Models\PublicacionExistencia;
use App\Models\User;
use App\Services\PublicacionExistenciaService;
use App\Support\DocumentoNumero;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PublicacionExistenciaResource extends Resource
{
    protected static ?string $model = PublicacionExistencia::class;

    protected static ?string $slug = 'publicaciones-existencias';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'publicación de existencias';

    protected static ?string $pluralModelLabel = 'publicaciones de existencias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Publicaciones';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la publicación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->default(fn (): string => DocumentoNumero::generar('PUB'))
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(30),
                        DateTimePicker::make('fecha_ingreso')
                            ->label('Fecha de ingreso')
                            ->default(now())
                            ->required(),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->default(fn (): ?int => Filament::auth()->id())
                            ->disabled()
                            ->dehydrated(),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->default(PublicacionExistencia::ESTADO_BORRADOR)
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(20),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Existencias')
                    // ->description('Fotografía histórica. Estas líneas no alteran el stock operativo.')
                    ->schema([
                        Repeater::make('existencias')
                            ->label('Items de existencias')
                            ->relationship()
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(0)
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->relationship('producto', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->relationship('almacen', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->integer()
                                    ->minValue(0)
                                    ->required(),
                                TextInput::make('precio_unitario')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->prefix('Bs')
                                    ->minValue(0)
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('publicacion_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_ingreso')
                    ->label('Fecha de ingreso')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('existencias_count')
                    ->label('Items')
                    ->counts('existencias')
                    ->numeric(),
                TextColumn::make('publicada_en')
                    ->label('Publicada en')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('responsable_user_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => PublicacionExistencia::query()
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('publicar')
                        ->label('Publicar')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Publicar existencias')
                        ->modalDescription('La publicación quedará inmutable. No se modifica el stock operativo.')
                        ->authorize('publicar')
                        ->visible(fn (PublicacionExistencia $record): bool => $record->estado === PublicacionExistencia::ESTADO_BORRADOR)
                        ->action(function (PublicacionExistencia $record): void {
                            try {
                                app(PublicacionExistenciaService::class)->publicar($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Publicación registrada')
                                ->body('La fotografía de existencias fue publicada correctamente.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('fecha_ingreso', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicacionExistencias::route('/'),
            // 'create' => CreatePublicacionExistencia::route('/create'),
            // 'edit' => EditPublicacionExistencia::route('/{record}/edit'),
        ];
    }

    private static function usuarioActual(): User
    {
        $usuario = Filament::auth()->user();

        if (! $usuario instanceof User) {
            abort(403);
        }

        return $usuario;
    }

    private static function notificarError(DomainException $exception): void
    {
        Notification::make()
            ->title('No se pudo completar la acción')
            ->body($exception->getMessage())
            ->danger()
            ->send();
    }
}
