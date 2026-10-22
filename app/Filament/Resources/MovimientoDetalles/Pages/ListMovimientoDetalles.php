<?php

namespace App\Filament\Resources\MovimientoDetalles\Pages;

use App\Filament\Resources\MovimientoDetalles\MovimientoDetalleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMovimientoDetalles extends ListRecords
{
    protected static string $resource = MovimientoDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
