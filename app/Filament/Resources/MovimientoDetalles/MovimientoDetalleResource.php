<?php

namespace App\Filament\Resources\MovimientoDetalles;

use App\Filament\Resources\MovimientoDetalles\Pages\CreateMovimientoDetalle;
use App\Filament\Resources\MovimientoDetalles\Pages\EditMovimientoDetalle;
use App\Filament\Resources\MovimientoDetalles\Pages\ListMovimientoDetalles;
use App\Models\MovimientoDetalle;
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

class MovimientoDetalleResource extends Resource
{
    protected static ?string $model = MovimientoDetalle::class;

    protected static ?string $slug = 'movimiento-detalles';

    protected static ?string $modelLabel = 'detalle de movimiento';

    protected static ?string $pluralModelLabel = 'detalles de movimiento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static string|\UnitEnum|null $navigationGroup = 'Movimientos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('movimiento_id')
                    ->label('Movimiento')
                    ->relationship('movimiento', 'numero')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('producto_id')
                    ->label('Producto')
                    ->relationship('producto', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->required()
                    ->numeric()
                    ->integer(),
                TextInput::make('precio_unitario')
                    ->label('Precio unitario')
                    ->numeric()
                    ->prefix('Bs')
                    ->step('0.01'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movimiento_detalle_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('movimiento.numero')
                    ->label('Movimiento')
                    ->searchable(),
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('cantidad')
                    ->label('Cantidad')
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
            ])
            ->filters([
                SelectFilter::make('movimiento_id')
                    ->label('Movimiento')
                    ->relationship('movimiento', 'numero'),
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
            'index' => ListMovimientoDetalles::route('/'),
            'create' => CreateMovimientoDetalle::route('/create'),
            'edit' => EditMovimientoDetalle::route('/{record}/edit'),
        ];
    }
}
