<?php

namespace App\Filament\Resources\Movimientos;

use App\Filament\Resources\Movimientos\Pages\CreateMovimiento;
use App\Filament\Resources\Movimientos\Pages\EditMovimiento;
use App\Filament\Resources\Movimientos\Pages\ListMovimientos;
use App\Models\Movimiento;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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

class MovimientoResource extends Resource
{
    protected static ?string $model = Movimiento::class;

    protected static ?string $slug = 'movimientos';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'movimiento';

    protected static ?string $pluralModelLabel = 'movimientos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|\UnitEnum|null $navigationGroup = 'Movimientos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del movimiento')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        TextInput::make('tipo')
                            ->label('Tipo')
                            ->required()
                            ->maxLength(30)
                            ->datalist(['ENTRADA', 'SALIDA', 'TRANSFERENCIA', 'AJUSTE']),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->required()
                            ->default('BORRADOR')
                            ->maxLength(20),
                        DateTimePicker::make('fecha_movimiento')
                            ->label('Fecha del movimiento')
                            ->required()
                            ->default(now()),
                        DateTimePicker::make('confirmado_en')
                            ->label('Confirmado en'),
                    ]),
                Section::make('Ubicaciones')
                    ->columns(2)
                    ->schema([
                        Select::make('almacen_origen_id')
                            ->label('Almacén de origen')
                            ->relationship('almacenOrigen', 'nombre')
                            ->searchable()
                            ->preload(),
                        Select::make('almacen_destino_id')
                            ->label('Almacén de destino')
                            ->relationship('almacenDestino', 'nombre')
                            ->searchable()
                            ->preload(),
                    ]),
                Section::make('Referencias')
                    ->columns(2)
                    ->schema([
                        Select::make('orden_id')
                            ->label('Orden')
                            ->relationship('orden', 'numero')
                            ->searchable()
                            ->preload(),
                        Select::make('salida_id')
                            ->label('Salida de almacén')
                            ->relationship('salida', 'numero')
                            ->searchable()
                            ->preload(),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload(),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movimiento_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('fecha_movimiento')
                    ->label('Fecha del movimiento')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('almacenOrigen.nombre')
                    ->label('Almacén origen'),
                TextColumn::make('almacenDestino.nombre')
                    ->label('Almacén destino'),
                TextColumn::make('orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('salida.numero')
                    ->label('Salida')
                    ->searchable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
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
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(fn (): array => Movimiento::query()
                        ->distinct()
                        ->pluck('tipo', 'tipo')
                        ->all()),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => Movimiento::query()
                        ->distinct()
                        ->pluck('estado', 'estado')
                        ->all()),
                SelectFilter::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero'),
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
            'index' => ListMovimientos::route('/'),
            'create' => CreateMovimiento::route('/create'),
            'edit' => EditMovimiento::route('/{record}/edit'),
        ];
    }
}
