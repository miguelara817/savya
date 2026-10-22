<?php

namespace App\Filament\Resources\StockSaldos;

use App\Filament\Resources\StockSaldos\Pages\CreateStockSaldo;
use App\Filament\Resources\StockSaldos\Pages\EditStockSaldo;
use App\Filament\Resources\StockSaldos\Pages\ListStockSaldos;
use App\Models\StockSaldo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Almacenes y productos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                TextInput::make('cantidad_actual')
                    ->label('Cantidad actual')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->default(0),
                TextInput::make('cantidad_reservada')
                    ->label('Cantidad reservada')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->default(0),
            ]);
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
                    ->label('Cantidad actual')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cantidad_reservada')
                    ->label('Cantidad reservada')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('disponible')
                    ->label('Disponible')
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
            'index' => ListStockSaldos::route('/'),
            'create' => CreateStockSaldo::route('/create'),
            'edit' => EditStockSaldo::route('/{record}/edit'),
        ];
    }
}
