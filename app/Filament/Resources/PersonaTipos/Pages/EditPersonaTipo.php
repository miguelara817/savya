<?php

namespace App\Filament\Resources\PersonaTipos\Pages;

use App\Filament\Resources\PersonaTipos\PersonaTipoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPersonaTipo extends EditRecord
{
    protected static string $resource = PersonaTipoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
