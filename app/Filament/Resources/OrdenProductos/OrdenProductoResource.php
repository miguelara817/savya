<?php

namespace App\Filament\Resources\OrdenProductos;

use App\Filament\Resources\OrdenProductos\Pages\ListOrdenProductos;
use App\Models\OrdenProducto;
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

class OrdenProductoResource extends Resource
{
    protected static ?string $model = OrdenProducto::class;

    protected static ?string $slug = 'orden-productos';

    protected static ?string $modelLabel = 'producto de orden';

    protected static ?string $pluralModelLabel = 'productos de orden';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('existencia_id')
                    ->label('Existencia publicada')
                    ->relationship('existencia', 'existencia_id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cantidad_solicitada')
                    ->label('Cantidad solicitada')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1),
                TextInput::make('precio_unitario')
                    ->label('Precio unitario')
                    ->required()
                    ->numeric()
                    ->prefix('Bs')
                    ->step('0.01'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden_producto_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('existencia.existencia_id')
                    ->label('Existencia')
                    ->searchable(),
                TextColumn::make('cantidad_solicitada')
                    ->label('Cantidad solicitada')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('precio_unitario')
                    ->label('Precio unitario')
                    ->money('BOB')
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
                SelectFilter::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero'),
                SelectFilter::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre'),
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
            'index' => ListOrdenProductos::route('/'),
        ];
    }
}
