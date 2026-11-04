<?php

namespace App\Filament\Resources\ServiceDataInfos;

use App\Filament\Resources\ServiceDataInfos\Pages\CreateServiceDataInfo;
use App\Filament\Resources\ServiceDataInfos\Pages\EditServiceDataInfo;
use App\Filament\Resources\ServiceDataInfos\Pages\ListServiceDataInfos;
use App\Filament\Resources\ServiceDataInfos\Schemas\ServiceDataInfoForm;
use App\Filament\Resources\ServiceDataInfos\Tables\ServiceDataInfosTable;
use App\Models\ServiceDataInfo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Gate;

class ServiceDataInfoResource extends Resource
{
    protected static ?string $model = ServiceDataInfo::class;

    protected static ?string $slug = 'service-data-info';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $modelLabel = 'servicios externos';

    protected static ?string $pluralModelLabel = 'servicios externos';

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::GlobeAlt;

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', ServiceDataInfo::class);
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceDataInfoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceDataInfosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceDataInfos::route('/'),
            // 'create' => CreateServiceDataInfo::route('/create'),
            // 'edit' => EditServiceDataInfo::route('/{record}/edit'),
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
