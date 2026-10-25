<?php

namespace App\Filament\Resources\SalidaAlmacens;

use App\Filament\Resources\Entregas\EntregaResource;
use App\Filament\Resources\SalidaAlmacens\Pages\EditSalidaAlmacen;
use App\Filament\Resources\SalidaAlmacens\Pages\ListSalidaAlmacens;
use App\Filament\Resources\SalidaAlmacens\Pages\ViewSalidaAlmacen;
use App\Models\Entrega;
use App\Models\Orden;
use App\Models\OrdenProducto;
use App\Models\SalidaAlmacen;
use App\Models\User;
use App\Services\EntregaService;
use App\Services\SalidaAlmacenService;
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
use Filament\Forms\Components\FileUpload;
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

class SalidaAlmacenResource extends Resource
{
    protected static ?string $model = SalidaAlmacen::class;

    protected static ?string $slug = 'salidas-almacen';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'salida de almacén';

    protected static ?string $pluralModelLabel = 'salidas de almacén';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Salidas y entregas';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la salida')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(30),
                        Select::make('orden_id')
                            ->label('Orden')
                            ->relationship('orden', 'numero')
                            ->getOptionLabelFromRecordUsing(fn (Orden $record): string => filled($record->numero)
                                ? $record->numero
                                : 'Orden #'.$record->getKey())
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Select::make('almacen_id')
                            ->label('Almacén')
                            ->relationship('almacen', 'nombre')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        DateTimePicker::make('fecha_salida')
                            ->label('Fecha de salida'),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Productos a despachar')
                    ->schema([
                        Repeater::make('detalles')
                            ->label('Detalle de la salida')
                            ->relationship()
                            ->columns(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Select::make('orden_producto_id')
                                    ->label('Producto de la orden')
                                    ->relationship('ordenProducto', 'orden_producto_id')
                                    ->getOptionLabelFromRecordUsing(fn (OrdenProducto $record): string => sprintf(
                                        '%s (solicitado: %d)',
                                        $record->producto?->nombre ?? 'Producto',
                                        $record->cantidad_solicitada,
                                    ))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('cantidad_despachada')
                                    ->label('Cantidad despachada')
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Firma del responsable')
                    ->columns(2)
                    ->schema([
                        TextInput::make('firma_responsable_nombre')
                            ->label('Nombre del responsable')
                            ->maxLength(200),
                        DateTimePicker::make('firmado_en')
                            ->label('Firmado en')
                            ->disabled()
                            ->dehydrated(),
                        FileUpload::make('firma_responsable_archivo')
                            ->label('Archivo de firma')
                            ->directory('firmas/salidas')
                            ->columnSpanFull(),
                        DateTimePicker::make('confirmado_en')
                            ->label('Confirmado en')
                            ->disabled()
                            ->dehydrated(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salida_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('almacen.nombre')
                    ->label('Almacén'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('detalles_count')
                    ->label('Líneas')
                    ->counts('detalles')
                    ->numeric(),
                TextColumn::make('fecha_salida')
                    ->label('Fecha de salida')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_responsable_nombre')
                    ->label('Firmante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_responsable_archivo')
                    ->label('Archivo de firma')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firmado_en')
                    ->label('Firmado en')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('confirmado_en')
                    ->label('Confirmado en')
                    ->dateTime()
                    ->sortable()
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
                SelectFilter::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero')
                    ->getOptionLabelFromRecordUsing(fn (Orden $record): string => filled($record->numero)
                        ? $record->numero
                        : 'Orden #'.$record->getKey()),
                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->relationship('almacen', 'nombre'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => SalidaAlmacen::query()
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('confirmar')
                        ->label('Confirmar salida')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar salida de almacén')
                        ->modalDescription('Se descontará el stock físico y se liberará la reserva utilizada. Esta acción es irreversible.')
                        ->authorize('confirmar')
                        ->visible(fn (SalidaAlmacen $record): bool => $record->estado === SalidaAlmacen::ESTADO_BORRADOR)
                        ->action(function (SalidaAlmacen $record): void {
                            try {
                                app(SalidaAlmacenService::class)->confirmar($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Salida confirmada')
                                ->body('El stock físico y las reservas fueron actualizados.')
                                ->success()
                                ->send();
                        }),
                    Action::make('registrarEntrega')
                        ->label('Registrar entrega')
                        ->icon(Heroicon::OutlinedInboxArrowDown)
                        ->color('gray')
                        ->authorize(fn (): bool => self::usuarioActual()->can('create', Entrega::class))
                        ->visible(fn (SalidaAlmacen $record): bool => $record->estado === SalidaAlmacen::ESTADO_CONFIRMADA)
                        ->action(function (SalidaAlmacen $record) {
                            try {
                                $entrega = app(EntregaService::class)->prepararDesdeSalida($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return null;
                            }

                            Notification::make()
                                ->title('Entrega preparada en borrador')
                                ->body('Revise las cantidades y confirme la entrega al momento de la recepción.')
                                ->success()
                                ->send();

                            return redirect(EntregaResource::getUrl('edit', ['record' => $entrega]));
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalidaAlmacens::route('/'),
            'view' => ViewSalidaAlmacen::route('/{record}'),
            'edit' => EditSalidaAlmacen::route('/{record}/edit'),
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
