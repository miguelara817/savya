<?php

namespace App\Filament\Resources\Personas;

use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Filament\Resources\Personas\Pages\EditPersona;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Models\BoDepartamento;
use App\Models\BoLocalidad;
use App\Models\BoMunicipio;
use App\Models\BoProvincia;
use App\Models\Persona;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static ?string $slug = 'personas';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'persona';

    protected static ?string $pluralModelLabel = 'personas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Personas y usuarios';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('persona_tipo_id')
                    ->label('Tipo de persona')
                    ->relationship('tipo', 'valor')
                    // ->searchable()
                    // ->preload()
                    ->required()
                    ->live()
                    ->columnSpan(2),
                TextInput::make('nombre')
                    // ->label('Nombre')
                    ->label(fn (Get $get) => $get('persona_tipo_id') == 2 ? 'Razón social' : 'Nombre')
                    ->required()
                    ->maxLength(100),
                TextInput::make('apellido')
                    ->label('Apellido')
                    ->maxLength(100)
                    ->hidden(fn (Get $get) => $get('persona_tipo_id') == 2),
                TextInput::make('carnet')
                    // ->label('Carnet')
                    ->label(fn (Get $get) => $get('persona_tipo_id') == 2 ? 'NIT' : 'Carnet')
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel()
                    ->required()
                    ->maxLength(8),
                TextInput::make('correo')
                    ->label('Correo')
                    ->email()
                    ->maxLength(150),
                TextInput::make('direccion')
                    ->label('Dirección')
                    ->maxLength(250),
                Select::make('bo_departamentos_id')
                    ->label('Departamento')
                    ->options(fn () => BoDepartamento::query()->orderBy('nombre')->pluck('nombre', 'bo_departamentos_id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('bo_provincias_id', null);
                        $set('bo_municipios_id', null);
                        $set('bo_localidades_id', null);
                    }),
                Select::make('bo_provincias_id')
                    ->label('Provincia')
                    ->options(fn (Get $get) => BoProvincia::query()
                        ->where('bo_departamentos_id', $get('bo_departamentos_id'))
                        ->orderBy('nombre')
                        ->pluck('nombre', 'bo_provincias_id'))
                    ->searchable()
                    ->live()
                    ->disabled(fn (Get $get): bool => blank($get('bo_departamentos_id')))
                    ->afterStateUpdated(function (callable $set): void {
                        $set('bo_municipios_id', null);
                        $set('bo_localidades_id', null);
                    }),
                Select::make('bo_municipios_id')
                    ->label('Municipio')
                    ->options(fn (Get $get) => BoMunicipio::query()
                        ->where('bo_provincias_id', $get('bo_provincias_id'))
                        ->orderBy('nombre')
                        ->pluck('nombre', 'bo_municipios_id'))
                    ->searchable()
                    ->live()
                    ->disabled(fn (Get $get): bool => blank($get('bo_provincias_id')))
                    ->afterStateUpdated(fn (callable $set) => $set('bo_localidades_id', null)),
                Select::make('bo_localidades_id')
                    ->label('Localidad')
                    ->options(fn (Get $get) => BoLocalidad::query()
                        ->where('bo_municipios_id', $get('bo_municipios_id'))
                        ->orderBy('nombre')
                        ->pluck('nombre', 'bo_localidades_id'))
                    ->searchable()
                    ->disabled(fn (Get $get): bool => blank($get('bo_municipios_id'))),
                TextInput::make('registro_emapa')
                    ->label('Registro EMAPA')
                    ->maxLength(100),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('index')
                    ->label('N.º')
                    ->rowIndex(),
                TextColumn::make('persona_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tipo.valor')
                    ->label('Tipo'),
                TextColumn::make('nombre')
                    ->label('Nombre / Razón social')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido')
                    ->label('Apellido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('carnet')
                    ->label('Carnet / NIT')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('boDepartamento.nombre')
                    ->label('Departamento'),
                TextColumn::make('direccion')
                    ->label('Dirección')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('registro_emapa')
                    ->label('Registro EMAPA')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->columnSpanFull(),
                // TextColumn::make('created_at')
                //     ->label('Creado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('updated_at')
                //     ->label('Actualizado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('deleted_at')
                //     ->label('Eliminado')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('persona_tipo_id')
                    ->label('Tipo de persona')
                    ->relationship('tipo', 'valor'),
                TernaryFilter::make('is_active')
                    ->label('Activo'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
            // 'create' => CreatePersona::route('/create'),
            // 'edit' => EditPersona::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
