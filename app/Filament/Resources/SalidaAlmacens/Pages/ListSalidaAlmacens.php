<?php

namespace App\Filament\Resources\SalidaAlmacens\Pages;

use App\Filament\Resources\SalidaAlmacens\SalidaAlmacenResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalidaAlmacens extends ListRecords
{
    protected static string $resource = SalidaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
