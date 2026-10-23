<?php

namespace App\Filament\Resources\PublicacionExistencias;

use App\Filament\Resources\PublicacionExistencias\Pages\CreatePublicacionExistencia;
use App\Filament\Resources\PublicacionExistencias\Pages\EditPublicacionExistencia;
use App\Filament\Resources\PublicacionExistencias\Pages\ListPublicacionExistencias;
use App\Models\PublicacionExistencia;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PublicacionExistenciaResource extends Resource
{
    protected static ?string $model = PublicacionExistencia::class;

    protected static ?string $slug = 'publicaciones-existencias';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'publicación de existencias';

    protected static ?string $pluralModelLabel = 'publicaciones de existencias';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Publicaciones';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero')
                    ->label('Número')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('fecha_publicacion')
                    ->label('Fecha de publicación')
                    ->required()
                    ->default(now()),
                Select::make('responsable_user_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->default(fn (): ?int => Filament::auth()->id())
                    ->searchable()
                    ->preload()
                    ->disabled()->dehydrated(),
                TextInput::make('estado')
                    ->label('Estado')
                    ->required()
                    ->default('BORRADOR')
                    ->maxLength(20),
                // DateTimePicker::make('publicada_en')
                //     ->label('Publicada en'),
                Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('publicacion_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fecha_publicacion')
                    ->label('Fecha de publicación')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('publicada_en')
                    ->label('Publicada en')
                    ->dateTime()
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
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('responsable_user_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => PublicacionExistencia::query()
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
            'index' => ListPublicacionExistencias::route('/'),
            // 'create' => CreatePublicacionExistencia::route('/create'),
            // 'edit' => EditPublicacionExistencia::route('/{record}/edit'),
        ];
    }
}
