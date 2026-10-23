<?php

namespace App\Filament\Resources\OrdenPersonas;

use App\Filament\Resources\OrdenPersonas\Pages\ListOrdenPersonas;
use App\Models\OrdenPersona;
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

class OrdenPersonaResource extends Resource
{
    protected static ?string $model = OrdenPersona::class;

    protected static ?string $slug = 'orden-personas';

    protected static ?string $modelLabel = 'persona de orden';

    protected static ?string $pluralModelLabel = 'personas de orden';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'Pedidos';

    protected static ?int $navigationSort = 4;

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
                Select::make('persona_id')
                    ->label('Persona')
                    ->relationship('persona', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('funcion')
                    ->label('Función')
                    ->required()
                    ->maxLength(40)
                    ->datalist(['AUTORIZADO_RECOJO', 'CONTACTO', 'RECEPTOR']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('orden_persona_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden.numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('persona.nombre')
                    ->label('Persona')
                    ->searchable(),
                TextColumn::make('funcion')
                    ->label('Función')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('orden_id')
                    ->label('Orden')
                    ->relationship('orden', 'numero'),
                SelectFilter::make('persona_id')
                    ->label('Persona')
                    ->relationship('persona', 'nombre'),
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
            'index' => ListOrdenPersonas::route('/'),
        ];
    }
}
