<?php

namespace App\Filament\Resources\ServiceDataInfos\Pages;

use App\Filament\Resources\ServiceDataInfos\ServiceDataInfoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceDataInfos extends ListRecords
{
    protected static string $resource = ServiceDataInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
