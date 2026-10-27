<?php

namespace App\Filament\Resources\Productos;

use App\Filament\Resources\Productos\Pages\CreateProducto;
use App\Filament\Resources\Productos\Pages\EditProducto;
use App\Filament\Resources\Productos\Pages\ListProductos;
use App\Models\Producto;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductoResource extends Resource
{
    protected static ?string $model = Producto::class;

    protected static ?string $slug = 'productos';

    protected static ?string $recordTitleAttribute = 'nombre';

    protected static ?string $modelLabel = 'producto';

    protected static ?string $pluralModelLabel = 'productos';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Almacenes y productos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(200)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')
                    ->label('Slug')
                    ->maxLength(200),
                TextInput::make('codigo')
                    ->label('Código')
                    ->maxLength(50)
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('presentacion')
                    ->label('Presentación')
                    ->maxLength(50),
                // TextInput::make('unidad_medida')
                //     ->label('Unidad de medida')
                //     ->required()
                //     ->maxLength(30),
                Select::make('unidad_medida')
                    ->label('Unidad de medida')
                    ->options([
                        'kg' => 'Kilogramo [kg]',
                        'qq' => 'Quintal [qq]',
                    ])
                    ->required(),
                TextInput::make('marca')
                    ->label('Marca')
                    ->maxLength(50),
                TextInput::make('proveedor')
                    ->label('Proveedor')
                    ->maxLength(50),
                Textarea::make('caracteristicas')
                    ->label('Características')
                    ->columnSpanFull(),
                FileUpload::make('imagen_producto')
                    ->label('Imagen del producto')
                    ->disk('public')
                    ->directory('productos')
                    ->visibility('public')
                    ->image()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get) : string {
                        $slug = $get('slug') ?? 'producto';
                        $extension = $file->guessExtension() ?? 'bin';
                        return sprintf(
                            '%s-%s.%s',
                            $slug,
                            now()->format('YmdHis'),
                            $extension 
                        );
                    })
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(10240)
                    ->columnSpanFull(),
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
                // TextColumn::make('producto_id')
                //     ->label('ID')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('imagen_producto')
                    ->label('Imagen del Producto')
                    ->disk('public')
                    ->visibility('public')
                    ->square()
                    ->size(100),
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('unidad_medida')
                    ->label('Unidad de medida'),
                TextColumn::make('caracteristicas')
                    ->label('Características')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
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
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activo'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
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
            'index' => ListProductos::route('/'),
            // 'create' => CreateProducto::route('/create'),
            // 'edit' => EditProducto::route('/{record}/edit'),
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
