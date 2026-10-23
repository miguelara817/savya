<?php

namespace App\Filament\Resources\Existencias\Pages;

use App\Filament\Resources\Existencias\ExistenciaResource;
use Filament\Resources\Pages\ListRecords;

class ListExistencias extends ListRecords
{
    protected static string $resource = ExistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
