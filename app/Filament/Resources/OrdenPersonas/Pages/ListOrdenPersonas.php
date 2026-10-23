<?php

namespace App\Filament\Resources\OrdenPersonas\Pages;

use App\Filament\Resources\OrdenPersonas\OrdenPersonaResource;
use Filament\Resources\Pages\ListRecords;

class ListOrdenPersonas extends ListRecords
{
    protected static string $resource = OrdenPersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
