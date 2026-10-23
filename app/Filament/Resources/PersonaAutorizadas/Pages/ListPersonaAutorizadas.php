<?php

namespace App\Filament\Resources\PersonaAutorizadas\Pages;

use App\Filament\Resources\PersonaAutorizadas\PersonaAutorizadaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonaAutorizadas extends ListRecords
{
    protected static string $resource = PersonaAutorizadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
