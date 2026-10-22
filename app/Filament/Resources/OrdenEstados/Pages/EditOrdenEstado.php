<?php

namespace App\Filament\Resources\OrdenEstados\Pages;

use App\Filament\Resources\OrdenEstados\OrdenEstadoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdenEstado extends EditRecord
{
    protected static string $resource = OrdenEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
