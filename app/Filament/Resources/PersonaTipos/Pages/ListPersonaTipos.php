<?php

namespace App\Filament\Resources\PersonaTipos\Pages;

use App\Filament\Resources\PersonaTipos\PersonaTipoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonaTipos extends ListRecords
{
    protected static string $resource = PersonaTipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
