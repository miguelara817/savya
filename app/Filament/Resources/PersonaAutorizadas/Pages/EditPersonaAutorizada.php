<?php

namespace App\Filament\Resources\PersonaAutorizadas\Pages;

use App\Filament\Resources\PersonaAutorizadas\PersonaAutorizadaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonaAutorizada extends EditRecord
{
    protected static string $resource = PersonaAutorizadaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
