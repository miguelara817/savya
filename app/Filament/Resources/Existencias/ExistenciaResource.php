<?php

namespace App\Filament\Resources\Existencias;

use App\Filament\Resources\Existencias\Pages\CreateExistencia;
use App\Filament\Resources\Existencias\Pages\EditExistencia;
use App\Filament\Resources\Existencias\Pages\ListExistencias;
use App\Models\Existencia;
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

class ExistenciaResource extends Resource
{
    protected static ?string $model = Existencia::class;

    protected static ?string $slug = 'existencias';

    protected static ?string $modelLabel = 'existencia';

    protected static ?string $pluralModelLabel = 'existencias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Publicaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('publicacion_id')
                    ->label('Publicación')
                    ->relationship('publicacion', 'numero')
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
                    ->integer(),
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
                TextColumn::make('existencia_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('publicacion.numero')
                    ->label('Publicación')
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
                SelectFilter::make('publicacion_id')
                    ->label('Publicación')
                    ->relationship('publicacion', 'numero'),
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
            'index' => ListExistencias::route('/'),
            'create' => CreateExistencia::route('/create'),
            'edit' => EditExistencia::route('/{record}/edit'),
        ];
    }
}
