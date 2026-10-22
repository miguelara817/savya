<?php

namespace App\Filament\Resources\Ordens;

use App\Filament\Resources\Ordens\Pages\CreateOrden;
use App\Filament\Resources\Ordens\Pages\EditOrden;
use App\Filament\Resources\Ordens\Pages\ListOrdens;
use App\Models\Orden;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
            ->components([
                Section::make('Identificación')
                    ->columns(2)
                    ->schema([
                        TextInput::make('codigo_orden')
                            ->label('Código UUID')
                            ->default(fn (): string => (string) Str::uuid())
                            ->maxLength(36),
                        TextInput::make('numero')
                            ->label('Número')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
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
                            ->required(),
                        Select::make('orden_estado_id')
                            ->label('Estado')
                            ->relationship('estado', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('solicitante_user_id')
                            ->label('Solicitante')
                            ->relationship('solicitante', 'name')
                            ->searchable()
                            ->preload(),
                        DateTimePicker::make('fecha_registro')
                            ->label('Fecha de registro')
                            ->required()
                            ->default(now()),
                        DateTimePicker::make('fecha_efectivizacion')
                            ->label('Fecha de efectivización'),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
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
                EditAction::make(),
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
            'create' => CreateOrden::route('/create'),
            'edit' => EditOrden::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
