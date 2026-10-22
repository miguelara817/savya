<?php

namespace App\Filament\Resources\OrdenEstados;

use App\Filament\Resources\OrdenEstados\Pages\CreateOrdenEstado;
use App\Filament\Resources\OrdenEstados\Pages\EditOrdenEstado;
use App\Filament\Resources\OrdenEstados\Pages\ListOrdenEstados;
use App\Models\OrdenEstado;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrdenEstadoResource extends Resource
{
    protected static ?string $model = OrdenEstado::class;

    protected static ?string $slug = 'orden-estados';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'estado de orden';

    protected static ?string $pluralModelLabel = 'estados de orden';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->maxLength(40)
                    ->unique(ignoreRecord: true),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(80),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden_estado_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('codigo')
                    ->label('Código')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ListOrdenEstados::route('/'),
            'create' => CreateOrdenEstado::route('/create'),
            'edit' => EditOrdenEstado::route('/{record}/edit'),
        ];
    }
}
