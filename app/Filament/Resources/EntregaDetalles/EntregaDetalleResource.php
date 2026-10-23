<?php

namespace App\Filament\Resources\EntregaDetalles;

use App\Filament\Resources\EntregaDetalles\Pages\ListEntregaDetalles;
use App\Models\EntregaDetalle;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EntregaDetalleResource extends Resource
{
    protected static ?string $model = EntregaDetalle::class;

    protected static ?string $slug = 'entrega-detalles';

    protected static ?string $modelLabel = 'detalle de entrega';

    protected static ?string $pluralModelLabel = 'detalles de entrega';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Salidas y entregas';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('entrega_id')
                    ->label('Entrega')
                    ->relationship('entrega', 'numero')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('salida_detalle_id')
                    ->label('Detalle de salida')
                    ->relationship('salidaDetalle', 'salida_detalle_id')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('cantidad_entregada')
                    ->label('Cantidad entregada')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entrega_detalle_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('entrega.numero')
                    ->label('Entrega')
                    ->searchable(),
                TextColumn::make('salidaDetalle.salida.numero')
                    ->label('Salida')
                    ->searchable(),
                TextColumn::make('salidaDetalle.ordenProducto.producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('cantidad_entregada')
                    ->label('Cantidad entregada')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('entrega_id')
                    ->label('Entrega')
                    ->relationship('entrega', 'numero'),
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
            'index' => ListEntregaDetalles::route('/'),
        ];
    }
}
