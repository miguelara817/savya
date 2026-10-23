<?php

namespace App\Filament\Resources\OrdenProductos\Pages;

use App\Filament\Resources\OrdenProductos\OrdenProductoResource;
use Filament\Resources\Pages\ListRecords;

class ListOrdenProductos extends ListRecords
{
    protected static string $resource = OrdenProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
