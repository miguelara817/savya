<?php

namespace App\Filament\Resources\SalidaDetalles;

use App\Filament\Resources\SalidaDetalles\Pages\CreateSalidaDetalle;
use App\Filament\Resources\SalidaDetalles\Pages\EditSalidaDetalle;
use App\Filament\Resources\SalidaDetalles\Pages\ListSalidaDetalles;
use App\Models\SalidaDetalle;
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

class SalidaDetalleResource extends Resource
{
    protected static ?string $model = SalidaDetalle::class;

    protected static ?string $slug = 'salida-detalles';

    protected static ?string $modelLabel = 'detalle de salida';

    protected static ?string $pluralModelLabel = 'detalles de salida';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Salidas y entregas';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('salida_id')
                    ->label('Salida')
                    ->relationship('salida', 'numero')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('orden_producto_id')
                    ->label('Producto de orden')
                    ->relationship('ordenProducto', 'orden_producto_id')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cantidad_despachada')
                    ->label('Cantidad despachada')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salida_detalle_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('salida.numero')
                    ->label('Salida')
                    ->searchable(),
                TextColumn::make('ordenProducto.orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('ordenProducto.producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('cantidad_despachada')
                    ->label('Cantidad despachada')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('salida_id')
                    ->label('Salida')
                    ->relationship('salida', 'numero'),
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
            'index' => ListSalidaDetalles::route('/'),
            'create' => CreateSalidaDetalle::route('/create'),
            'edit' => EditSalidaDetalle::route('/{record}/edit'),
        ];
    }
}
