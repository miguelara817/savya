<?php

namespace App\Filament\Resources\StockSaldos;

use App\Filament\Resources\StockSaldos\Pages\ListStockSaldos;
use App\Models\StockSaldo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockSaldoResource extends Resource
{
    protected static ?string $model = StockSaldo::class;

    protected static ?string $slug = 'stock-saldos';

    protected static ?string $modelLabel = 'saldo de stock';

    protected static ?string $pluralModelLabel = 'saldos de stock';

    protected static ?string $navigationLabel = 'Stock por almacén';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Almacenes y productos';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stock_saldo_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable(),
                TextColumn::make('cantidad_actual')
                    ->label('Stock físico')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cantidad_reservada')
                    ->label('Stock reservado')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('disponible')
                    ->label('Stock disponible')
                    ->numeric(),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre'),
                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->relationship('almacen', 'nombre'),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockSaldos::route('/'),
        ];
    }
}
