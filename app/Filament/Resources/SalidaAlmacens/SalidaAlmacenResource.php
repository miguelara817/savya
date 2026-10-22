<?php

namespace App\Filament\Resources\SalidaAlmacens;

use App\Filament\Resources\SalidaAlmacens\Pages\CreateSalidaAlmacen;
use App\Filament\Resources\SalidaAlmacens\Pages\EditSalidaAlmacen;
use App\Filament\Resources\SalidaAlmacens\Pages\ListSalidaAlmacens;
use App\Models\SalidaAlmacen;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la salida')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        Select::make('orden_id')
                            ->label('Orden')
                            ->relationship('orden', 'numero')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('almacen_id')
                            ->label('Almacén')
                            ->relationship('almacen', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->required()
                            ->default('BORRADOR')
                            ->maxLength(30),
                        DateTimePicker::make('fecha_salida')
                            ->label('Fecha de salida'),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Firma del responsable')
                    ->columns(2)
                    ->schema([
                        TextInput::make('firma_responsable_nombre')
                            ->label('Nombre del responsable')
                            ->maxLength(200),
                        DateTimePicker::make('firmado_en')
                            ->label('Firmado en'),
                        FileUpload::make('firma_responsable_archivo')
                            ->label('Archivo de firma')
                            ->directory('firmas/salidas')
                            ->columnSpanFull(),
                        DateTimePicker::make('confirmado_en')
                            ->label('Confirmado en'),
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
                    ->relationship('orden', 'numero'),
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
                EditAction::make(),
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
            'create' => CreateSalidaAlmacen::route('/create'),
            'edit' => EditSalidaAlmacen::route('/{record}/edit'),
        ];
    }
}
