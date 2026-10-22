<?php

namespace App\Filament\Resources\PersonaTipos;

use App\Filament\Resources\PersonaTipos\Pages\CreatePersonaTipo;
use App\Filament\Resources\PersonaTipos\Pages\EditPersonaTipo;
use App\Filament\Resources\PersonaTipos\Pages\ListPersonaTipos;
use App\Models\PersonaTipo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PersonaTipoResource extends Resource
{
    protected static ?string $model = PersonaTipo::class;

    protected static ?string $slug = 'persona-tipos';

    protected static ?string $recordTitleAttribute = 'valor';

    protected static ?string $modelLabel = 'tipo de persona';

    protected static ?string $pluralModelLabel = 'tipos de persona';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Personas y usuarios';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('valor')
                    ->label('Valor')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona_tipo_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->searchable()
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
            'index' => ListPersonaTipos::route('/'),
            'create' => CreatePersonaTipo::route('/create'),
            'edit' => EditPersonaTipo::route('/{record}/edit'),
        ];
    }
}
