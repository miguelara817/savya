<?php

namespace App\Filament\Resources\Ordens;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Resources\Ordens\Pages\CreateOrden;
use App\Filament\Resources\Ordens\Pages\EditOrden;
use App\Filament\Resources\Ordens\Pages\ListOrdens;
use App\Filament\Resources\Ordens\Pages\ViewOrden;
use App\Filament\Resources\SalidaAlmacens\SalidaAlmacenResource;
use App\Models\Existencia;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\Producto;
use App\Models\SalidaAlmacen;
use App\Models\User;
use App\Services\OrdenService;
use App\Services\SalidaAlmacenService;
use App\Support\DocumentoNumero;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class OrdenResource extends Resource
{
    protected static ?string $model = Orden::class;

    protected static ?string $slug = 'ordenes';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'orden';

    protected static ?string $pluralModelLabel = 'órdenes';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->default(fn (): string => DocumentoNumero::generar('ORD'))
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(30),
                        // TextInput::make('codigo_orden')
                        //     ->label('Código UUID')
                        //     ->default(fn (): string => (string) Str::uuid())
                        //     ->disabled()
                        //     ->dehydrated()
                        //     ->maxLength(36),
                        Select::make('persona_id')
                            ->label('Cliente')
                            ->relationship('persona', 'nombre')
                            ->searchable()
                            ->preload(),
                        TextInput::make('razon_social')
                            ->label('Razón social')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->required()
                            ->maxLength(30),
                        TextInput::make('direccion_facturacion')
                            ->label('Dirección de facturación')
                            ->maxLength(250),
                        TextInput::make('correo_facturacion')
                            ->label('Correo de facturación')
                            ->email()
                            ->maxLength(150),
                    ]),
                Section::make('Operación')
                    ->columns(2)
                    ->schema([
                        Select::make('almacen_id')
                            ->label('Almacén')
                            ->relationship('almacen', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            ->dehydrated(),
                        Select::make('solicitante_user_id')
                            ->label('Solicitante')
                            ->relationship('solicitante', 'name')
                            ->default(fn (): ?int => Filament::auth()->id())
                            ->disabled()
                            ->dehydrated(),
                        DateTimePicker::make('fecha_registro')
                            ->label('Fecha de registro')
                            ->default(now())
                            ->disabled()
                            ->dehydrated(),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Productos solicitados')
                    ->description('Las líneas se toman de una publicación de existencias y conservan su precio histórico.')
                    ->schema([
                        Repeater::make('productos')
                            ->label('Líneas de la orden')
                            ->relationship()
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->schema([
                                Select::make('existencia_id')
                                    ->label('Existencia publicada')
                                    ->options(fn (): array => Existencia::query()
                                        ->with(['producto', 'almacen', 'publicacion'])
                                        ->orderByDesc('existencia_id')
                                        ->get()
                                        ->mapWithKeys(fn (Existencia $existencia): array => [
                                            $existencia->existencia_id => sprintf(
                                                '%s — %s (%s, disp. %d)',
                                                $existencia->producto?->nombre ?? 'Producto',
                                                $existencia->almacen?->nombre ?? 'Almacén',
                                                $existencia->publicacion?->estado ?? 'SIN PUBLICACIÓN',
                                                $existencia->cantidad,
                                            ),
                                        ])
                                        ->all())
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (?int $state, Set $set): void {
                                        $existencia = Existencia::query()->find($state);

                                        if ($existencia) {
                                            $set('producto_id', $existencia->producto_id);
                                            $set('precio_unitario', $existencia->precio_unitario);
                                        }
                                    })
                                    ->columnSpan(2),
                                Hidden::make('producto_id')
                                    ->required(),
                                TextInput::make('cantidad_solicitada')
                                    ->label('Cantidad')
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                                TextInput::make('precio_unitario')
                                    ->label('Precio unitario')
                                    ->numeric()
                                    ->prefix('Bs')
                                    ->minValue(0)
                                    ->required(),
                            ])
                            ->itemLabel(fn (array $state): ?string => filled($state['producto_id'] ?? null)
                                ? app(Producto::class)->newQuery()->whereKey($state['producto_id'])->value('nombre')
                                : null)
                            ->reorderable(false),
                    ]),
                Section::make('Personas adicionales')
                    ->schema([
                        Repeater::make('personasAdicionales')
                            ->label('Personas autorizadas')
                            ->relationship()
                            ->columns(2)
                            ->defaultItems(0)
                            ->schema([
                                Select::make('persona_id')
                                    ->label('Persona')
                                    ->relationship('persona', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('funcion')
                                    ->label('Función')
                                    ->maxLength(40)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Firma del cliente')
                    ->columns(2)
                    ->schema([
                        TextInput::make('firma_cliente_nombre')
                            ->label('Nombre del firmante')
                            ->maxLength(200),
                        TextInput::make('firma_cliente_documento')
                            ->label('Documento del firmante')
                            ->maxLength(50),
                        FileUpload::make('firma_cliente_archivo')
                            ->label('Archivo de firma')
                            ->directory('firmas/ordenes')
                            ->columnSpanFull(),
                        DateTimePicker::make('firmado_en')
                            ->label('Firmado en'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('codigo_orden')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('persona.nombre')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('razon_social')
                    ->label('Razón social')
                    ->searchable(),
                TextColumn::make('nit')
                    ->label('NIT')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('direccion_facturacion')
                    ->label('Dirección de facturación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('correo_facturacion')
                    ->label('Correo de facturación')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('almacen.nombre')
                    ->label('Almacén'),
                TextColumn::make('estado.nombre')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('productos_count')
                    ->label('Líneas')
                    ->counts('productos')
                    ->numeric(),
                TextColumn::make('solicitante.name')
                    ->label('Solicitante')
                    ->searchable(),
                TextColumn::make('fecha_registro')
                    ->label('Fecha de registro')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('fecha_efectivizacion')
                    ->label('Fecha de efectivización')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_cliente_nombre')
                    ->label('Firmante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_cliente_documento')
                    ->label('Documento del firmante')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_cliente_archivo')
                    ->label('Archivo de firma')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firmado_en')
                    ->label('Firmado en')
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('persona_id')
                    ->label('Cliente')
                    ->relationship('persona', 'nombre'),
                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->relationship('almacen', 'nombre'),
                SelectFilter::make('orden_estado_id')
                    ->label('Estado')
                    ->relationship('estado', 'nombre'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('enviarAprobacion')
                        ->label('Enviar a aprobación')
                        ->icon(Heroicon::OutlinedPaperAirplane)
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Enviar orden a aprobación')
                        ->modalDescription('La orden quedará bloqueada para edición hasta que sea aprobada, rechazada o cancelada.')
                        ->authorize('enviarAprobacion')
                        ->visible(fn (Orden $record): bool => $record->estaEnEstado(OrdenEstadoCodigo::Borrador))
                        ->action(function (Orden $record): void {
                            try {
                                app(OrdenService::class)->enviarAprobacion($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Orden enviada a aprobación')
                                ->success()
                                ->send();
                        }),
                    Action::make('aprobar')
                        ->label('Aprobar y reservar stock')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aprobar orden')
                        ->modalDescription('Se reservará el stock de todas las líneas de forma atómica. Esta acción no se puede deshacer automáticamente.')
                        ->authorize('aprobar')
                        ->visible(fn (Orden $record): bool => $record->estaEnEstado(OrdenEstadoCodigo::PendienteAprobacion))
                        ->action(function (Orden $record): void {
                            try {
                                app(OrdenService::class)->aprobar($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Orden aprobada y stock reservado')
                                ->success()
                                ->send();
                        }),
                    Action::make('rechazar')
                        ->label('Rechazar orden')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->schema([
                            Textarea::make('motivo')
                                ->label('Motivo del rechazo')
                                ->required()
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->modalHeading('Rechazar orden')
                        ->modalSubmitActionLabel('Rechazar')
                        ->authorize('rechazar')
                        ->visible(fn (Orden $record): bool => $record->estaEnEstado(OrdenEstadoCodigo::PendienteAprobacion))
                        ->action(function (Orden $record, array $data): void {
                            try {
                                app(OrdenService::class)->rechazar($record, self::usuarioActual(), $data['motivo']);
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Orden rechazada')
                                ->success()
                                ->send();
                        }),
                    Action::make('cancelar')
                        ->label('Cancelar orden')
                        ->icon(Heroicon::OutlinedLockClosed)
                        ->color('danger')
                        ->schema([
                            Textarea::make('motivo')
                                ->label('Motivo de la cancelación')
                                ->required()
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->modalHeading('Cancelar orden')
                        ->modalDescription('Se liberarán únicamente las reservas pendientes. Las cantidades ya despachadas no se revertirán.')
                        ->modalSubmitActionLabel('Cancelar orden')
                        ->authorize('cancelar')
                        ->visible(fn (Orden $record): bool => ! $record->estaEnEstado(
                            OrdenEstadoCodigo::Completada,
                            OrdenEstadoCodigo::Rechazada,
                            OrdenEstadoCodigo::Cancelada,
                            OrdenEstadoCodigo::Anulado,
                        ))
                        ->action(function (Orden $record, array $data): void {
                            try {
                                app(OrdenService::class)->cancelar($record, self::usuarioActual(), $data['motivo']);
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Orden cancelada')
                                ->success()
                                ->send();
                        }),
                    Action::make('prepararSalida')
                        ->label('Preparar salida')
                        ->icon(Heroicon::OutlinedTruck)
                        ->color('gray')
                        ->authorize(fn (): bool => self::usuarioActual()->can('create', SalidaAlmacen::class))
                        ->visible(fn (Orden $record): bool => $record->estaEnCurso())
                        ->action(function (Orden $record) {
                            try {
                                $salida = app(SalidaAlmacenService::class)->prepararDesdeOrden($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return null;
                            }

                            Notification::make()
                                ->title('Salida preparada en borrador')
                                ->body('Revise las cantidades y confirme la salida cuando se realice el despacho físico.')
                                ->success()
                                ->send();

                            return redirect(SalidaAlmacenResource::getUrl('edit', ['record' => $salida]));
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrdens::route('/'),
            // 'create' => CreateOrden::route('/create'),
            // 'view' => ViewOrden::route('/{record}'),
            // 'edit' => EditOrden::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function estadoInicialId(): ?int
    {
        return OrdenEstado::query()
            ->where('codigo', OrdenEstadoCodigo::Borrador->value)
            ->value('orden_estado_id');
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
