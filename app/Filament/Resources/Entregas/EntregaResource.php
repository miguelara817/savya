<?php

namespace App\Filament\Resources\Entregas;

use App\Filament\Resources\Entregas\Pages\CreateEntrega;
use App\Filament\Resources\Entregas\Pages\EditEntrega;
use App\Filament\Resources\Entregas\Pages\ListEntregas;
use App\Models\Entrega;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EntregaResource extends Resource
{
    protected static ?string $model = Entrega::class;

    protected static ?string $slug = 'entregas';

    protected static ?string $recordTitleAttribute = 'numero';

    protected static ?string $modelLabel = 'entrega';

    protected static ?string $pluralModelLabel = 'entregas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Salidas y entregas';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la entrega')
                    ->columns(2)
                    ->schema([
                        TextInput::make('numero')
                            ->label('Número')
                            ->required()
                            ->maxLength(30)
                            ->unique(ignoreRecord: true),
                        Select::make('salida_id')
                            ->label('Salida')
                            ->relationship('salida', 'numero')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('estado')
                            ->label('Estado')
                            ->required()
                            ->default('BORRADOR')
                            ->maxLength(30),
                        DateTimePicker::make('fecha_entrega')
                            ->label('Fecha de entrega'),
                        Select::make('responsable_user_id')
                            ->label('Responsable')
                            ->relationship('responsable', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('receptor_persona_id')
                            ->label('Persona receptora')
                            ->relationship('receptor', 'nombre')
                            ->searchable()
                            ->preload(),
                        TextInput::make('receptor_nombre')
                            ->label('Nombre del receptor')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('receptor_documento')
                            ->label('Documento del receptor')
                            ->maxLength(50),
                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->columnSpanFull(),
                    ]),
                Section::make('Firma de conformidad')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('firma_receptor_archivo')
                            ->label('Archivo de firma')
                            ->directory('firmas/entregas')
                            ->columnSpanFull(),
                        DateTimePicker::make('firmado_en')
                            ->label('Firmado en'),
                        DateTimePicker::make('confirmado_en')
                            ->label('Confirmado en'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('entrega_id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('salida.numero')
                    ->label('Salida')
                    ->searchable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),
                TextColumn::make('fecha_entrega')
                    ->label('Fecha de entrega')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable(),
                TextColumn::make('receptor.nombre')
                    ->label('Persona receptora')
                    ->searchable(),
                TextColumn::make('receptor_nombre')
                    ->label('Nombre del receptor')
                    ->searchable(),
                TextColumn::make('receptor_documento')
                    ->label('Documento del receptor')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observaciones')
                    ->label('Observaciones')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firma_receptor_archivo')
                    ->label('Archivo de firma')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('firmado_en')
                    ->label('Firmado en')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('confirmado_en')
                    ->label('Confirmado en')
                    ->dateTime()
                    ->sortable()
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
                SelectFilter::make('salida_id')
                    ->label('Salida')
                    ->relationship('salida', 'numero'),
                SelectFilter::make('receptor_persona_id')
                    ->label('Persona receptora')
                    ->relationship('receptor', 'nombre'),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => Entrega::query()
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
            'index' => ListEntregas::route('/'),
            'create' => CreateEntrega::route('/create'),
            'edit' => EditEntrega::route('/{record}/edit'),
        ];
    }
}
