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
use Closure;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicacionExistenciaResource extends Resource
{
    protected static ?string $model = PublicacionExistencia::class;

    protected static ?string $slug = 'existencias';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'Existencias';

    protected static ?string $pluralModelLabel = 'Existencias';

    protected static ?string $navigationLabel = 'Cargas iniciales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Almacenes y productos';

    protected static ?int $navigationSort = 3;

    public static function canEdit(Model $record): bool
    {
        return $record->estado === PublicacionExistencia::ESTADO_BORRADOR && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return $record->estado === PublicacionExistencia::ESTADO_BORRADOR && parent::canDelete($record);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos de la carga inicial')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->default(fn (): string => DocumentoNumero::generar('PUB'))
                            ->disabled()
                            ->dehydrated(false)
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
                            ->dehydrated(false),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->default(PublicacionExistencia::ESTADO_BORRADOR)
                            ->disabled()
                            ->dehydrated(false)
                            ->maxLength(20),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Existencias')
                    ->description('Guarda un borrador y confirma la carga para inicializar el stock. Cada producto y almacén admite una sola carga inicial.')
                    ->schema([
                        Repeater::make('existencias')
                            ->label('Items de existencias')
                            ->relationship()
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(0)
                            ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                                $combinaciones = [];

                                foreach ($value ?? [] as $linea) {
                                    $clave = ($linea['producto_id'] ?? '').':'.($linea['almacen_id'] ?? '');

                                    if (isset($combinaciones[$clave])) {
                                        $fail('No se puede repetir el mismo producto y almacén en una carga.');

                                        return;
                                    }

                                    $combinaciones[$clave] = true;
                                }
                            })
                            ->schema([
                                Select::make('producto_id')
                                    ->label('Producto')
                                    ->relationship('producto', 'nombre', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->relationship('almacen', 'nombre', modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true))
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
                    EditAction::make()
                        ->authorize(fn (PublicacionExistencia $record): bool => self::canEdit($record))
                        ->databaseTransaction(),
                    DeleteAction::make()
                        ->authorize(fn (PublicacionExistencia $record): bool => self::canDelete($record)),
                    Action::make('publicar')
                        ->label('Confirmar carga inicial')
                        ->icon(Heroicon::OutlinedCheckBadge)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar carga inicial')
                        ->modalDescription('Se creará el stock físico con las cantidades registradas y reserva cero. La carga quedará inmutable. Si algún producto ya tiene saldo en ese almacén, no se aplicará ninguna línea.')
                        ->authorize('publicar')
                        ->visible(fn (PublicacionExistencia $record): bool => $record->estado === PublicacionExistencia::ESTADO_BORRADOR)
                        ->action(function (PublicacionExistencia $record): void {
                            try {
                                app(PublicacionExistenciaService::class)->registrarCargaInicial($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Carga inicial confirmada')
                                ->body('El stock ya está disponible para consulta y reserva.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->toolbarActions([])
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
