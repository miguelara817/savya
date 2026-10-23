<?php

namespace App\Filament\Resources\MovimientoHistorialEstados\Pages;

use App\Filament\Resources\MovimientoHistorialEstados\MovimientoHistorialEstadoResource;
use Filament\Resources\Pages\ListRecords;

class ListMovimientoHistorialEstados extends ListRecords
{
    protected static string $resource = MovimientoHistorialEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
