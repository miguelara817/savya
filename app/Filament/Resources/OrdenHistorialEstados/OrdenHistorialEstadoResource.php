<?php

namespace App\Filament\Resources\OrdenHistorialEstados;

use App\Filament\Resources\OrdenHistorialEstados\Pages\CreateOrdenHistorialEstado;
use App\Filament\Resources\OrdenHistorialEstados\Pages\EditOrdenHistorialEstado;
use App\Filament\Resources\OrdenHistorialEstados\Pages\ListOrdenHistorialEstados;
use App\Models\OrdenHistorialEstado;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdenHistorialEstadoResource extends Resource
{
    protected static ?string $model = OrdenHistorialEstado::class;

    protected static ?string $slug = 'orden-historial-estados';

    protected static ?string $modelLabel = 'historial de estado de orden';

    protected static ?string $pluralModelLabel = 'historial de estados de orden';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 5;

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
                Select::make('orden_estado_id')
                    ->label('Estado')
                    ->relationship('estado', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('responsable_user_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->preload(),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden_historial_estado_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('estado.nombre')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero'),
                SelectFilter::make('orden_estado_id')
                    ->label('Estado')
                    ->relationship('estado', 'nombre'),
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
            'index' => ListOrdenHistorialEstados::route('/'),
            'create' => CreateOrdenHistorialEstado::route('/create'),
            'edit' => EditOrdenHistorialEstado::route('/{record}/edit'),
        ];
    }
}
