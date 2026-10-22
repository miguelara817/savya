<?php

namespace App\Filament\Resources\OrdenEstados\Pages;

use App\Filament\Resources\OrdenEstados\OrdenEstadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdenEstados extends ListRecords
{
    protected static string $resource = OrdenEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
