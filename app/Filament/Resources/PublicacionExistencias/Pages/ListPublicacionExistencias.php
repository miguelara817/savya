<?php

namespace App\Filament\Resources\PublicacionExistencias\Pages;

use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPublicacionExistencias extends ListRecords
{
    protected static string $resource = PublicacionExistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
