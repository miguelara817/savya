<?php

namespace App\Filament\Resources\SalidaDetalles\Pages;

use App\Filament\Resources\SalidaDetalles\SalidaDetalleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalidaDetalles extends ListRecords
{
    protected static string $resource = SalidaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
