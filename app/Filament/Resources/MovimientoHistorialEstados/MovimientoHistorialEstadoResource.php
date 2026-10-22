<?php

namespace App\Filament\Resources\MovimientoHistorialEstados;

use App\Filament\Resources\MovimientoHistorialEstados\Pages\CreateMovimientoHistorialEstado;
use App\Filament\Resources\MovimientoHistorialEstados\Pages\EditMovimientoHistorialEstado;
use App\Filament\Resources\MovimientoHistorialEstados\Pages\ListMovimientoHistorialEstados;
use App\Models\MovimientoHistorialEstado;
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

class MovimientoHistorialEstadoResource extends Resource
{
    protected static ?string $model = MovimientoHistorialEstado::class;

    protected static ?string $slug = 'movimiento-historial-estados';

    protected static ?string $modelLabel = 'historial de estado de movimiento';

    protected static ?string $pluralModelLabel = 'historial de estados de movimiento';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static string|\UnitEnum|null $navigationGroup = 'Movimientos';

    protected static ?int $navigationSort = 3;

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
                TextInput::make('estado')
                    ->label('Estado')
                    ->required()
                    ->maxLength(20)
                    ->datalist(['BORRADOR', 'CONFIRMADO', 'ANULADO']),
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
                TextColumn::make('movimiento_historial_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('movimiento.numero')
                    ->label('Movimiento')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
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
                SelectFilter::make('movimiento_id')
                    ->label('Movimiento')
                    ->relationship('movimiento', 'numero'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => MovimientoHistorialEstado::query()
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
            'index' => ListMovimientoHistorialEstados::route('/'),
            'create' => CreateMovimientoHistorialEstado::route('/create'),
            'edit' => EditMovimientoHistorialEstado::route('/{record}/edit'),
        ];
    }
}
