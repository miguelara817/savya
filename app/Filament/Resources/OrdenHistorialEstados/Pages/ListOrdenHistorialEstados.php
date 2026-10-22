<?php

namespace App\Filament\Resources\OrdenHistorialEstados\Pages;

use App\Filament\Resources\OrdenHistorialEstados\OrdenHistorialEstadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrdenHistorialEstados extends ListRecords
{
    protected static string $resource = OrdenHistorialEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
