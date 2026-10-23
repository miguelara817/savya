<?php

namespace App\Filament\Resources\Entregas;

use App\Filament\Resources\Entregas\Pages\EditEntrega;
use App\Filament\Resources\Entregas\Pages\ListEntregas;
use App\Filament\Resources\Entregas\Pages\ViewEntrega;
use App\Models\Entrega;
use App\Models\SalidaDetalle;
use App\Models\User;
use App\Services\EntregaService;
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

class EntregaResource extends Resource
{
    protected static ?string $model = Entrega::class;

    protected static ?string $slug = 'entregas';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'entrega';

    protected static ?string $pluralModelLabel = 'entregas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Salidas y entregas';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la entrega')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(30),
                        Select::make('salida_id')
                            ->label('Salida')
                            ->relationship('salida', 'numero')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        DateTimePicker::make('fecha_entrega')
                            ->label('Fecha de entrega'),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->disabled()
                            ->dehydrated(),
                        Select::make('receptor_persona_id')
                            ->label('Persona receptora')
                            ->relationship('receptor', 'nombre')
                            ->searchable()
                            ->preload(),
                        TextInput::make('receptor_nombre')
                            ->label('Nombre del receptor')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('receptor_documento')
                            ->label('Documento del receptor')
                            ->maxLength(50),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Productos entregados')
                    ->schema([
                        Repeater::make('detalles')
                            ->label('Detalle de la entrega')
                            ->relationship()
                            ->columns(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->schema([
                                Select::make('salida_detalle_id')
                                    ->label('Producto despachado')
                                    ->relationship('salidaDetalle', 'salida_detalle_id')
                                    ->getOptionLabelFromRecordUsing(fn (SalidaDetalle $record): string => sprintf(
                                        '%s (despachado: %d)',
                                        $record->ordenProducto?->producto?->nombre ?? 'Producto',
                                        $record->cantidad_despachada,
                                    ))
                                    ->disabled()
                                    ->dehydrated()
                                    ->required(),
                                TextInput::make('cantidad_entregada')
                                    ->label('Cantidad entregada')
                                    ->integer()
                                    ->minValue(1)
                                    ->required(),
                            ]),
                    ]),
                Section::make('Firma de conformidad')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('firma_receptor_archivo')
                            ->label('Archivo de firma')
                            ->directory('firmas/entregas')
                            ->columnSpanFull(),
                        DateTimePicker::make('firmado_en')
                            ->label('Firmado en')
                            ->disabled()
                            ->dehydrated(),
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
                TextColumn::make('entrega_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salida.numero')
                    ->label('Salida')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('detalles_count')
                    ->label('Líneas')
                    ->counts('detalles')
                    ->numeric(),
                TextColumn::make('fecha_entrega')
                    ->label('Fecha de entrega')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('receptor.nombre')
                    ->label('Persona receptora')
                    ->searchable(),
                TextColumn::make('receptor_nombre')
                    ->label('Nombre del receptor')
                    ->searchable(),
                TextColumn::make('receptor_documento')
                    ->label('Documento del receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_receptor_archivo')
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
                SelectFilter::make('salida_id')
                    ->label('Salida')
                    ->relationship('salida', 'numero'),
                SelectFilter::make('receptor_persona_id')
                    ->label('Persona receptora')
                    ->relationship('receptor', 'nombre'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => Entrega::query()
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->all()),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('confirmar')
                        ->label('Confirmar entrega')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar entrega')
                        ->modalDescription('Se registrará la conformidad del receptor y la orden podrá completarse si todas las cantidades fueron entregadas. No se descuenta stock nuevamente.')
                        ->authorize('confirmar')
                        ->visible(fn (Entrega $record): bool => $record->estado === Entrega::ESTADO_BORRADOR)
                        ->action(function (Entrega $record): void {
                            try {
                                app(EntregaService::class)->confirmar($record, self::usuarioActual());
                            } catch (DomainException $exception) {
                                self::notificarError($exception);

                                return;
                            }

                            Notification::make()
                                ->title('Entrega confirmada')
                                ->success()
                                ->send();
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
            'index' => ListEntregas::route('/'),
            'view' => ViewEntrega::route('/{record}'),
            'edit' => EditEntrega::route('/{record}/edit'),
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
