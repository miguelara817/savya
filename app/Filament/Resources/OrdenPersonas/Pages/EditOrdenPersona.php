<?php

namespace App\Filament\Resources\OrdenPersonas\Pages;

use App\Filament\Resources\OrdenPersonas\OrdenPersonaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdenPersona extends EditRecord
{
    protected static string $resource = OrdenPersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
