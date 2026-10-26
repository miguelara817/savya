<?php

namespace App\Filament\Resources\ReservaStocks;

use App\Filament\Resources\ReservaStocks\Pages\ListReservaStocks;
use App\Models\ReservaStock;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservaStockResource extends Resource
{
    protected static ?string $model = ReservaStock::class;

    protected static ?string $slug = 'reservas-stock';

    protected static ?string $modelLabel = 'reserva de stock';

    protected static ?string $pluralModelLabel = 'reservas de stock';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('orden_producto_id')
                    ->label('Producto de orden')
                    ->relationship('ordenProducto', 'orden_producto_id')
                    ->searchable()
                    ->preload()
                    ->required(),
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
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1),
                TextInput::make('estado')
                    ->label('Estado')
                    ->required()
                    ->default('ACTIVA')
                    ->maxLength(20),
                DateTimePicker::make('reservada_en')
                    ->label('Reservada en')
                    ->default(now()),
                DateTimePicker::make('liberada_en')
                    ->label('Liberada en'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // TextColumn::make('reserva_stock_id')
                //     ->label('ID')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('index')
                    ->label('N.º')
                    ->rowIndex(),
                TextColumn::make('ordenProducto.orden.codigo_orden')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('ordenProducto.orden.solicitante.name')
                    ->label('Solicitante')
                    ->searchable(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable(),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('reservada_en')
                    ->label('Reservada en')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('liberada_en')
                    ->label('Liberada en')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre'),
                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->relationship('almacen', 'nombre'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => ReservaStock::query()
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
            'index' => ListReservaStocks::route('/'),
        ];
    }
}
