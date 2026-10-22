<?php

namespace App\Filament\Resources\EntregaDetalles\Pages;

use App\Filament\Resources\EntregaDetalles\EntregaDetalleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEntregaDetalles extends ListRecords
{
    protected static string $resource = EntregaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
