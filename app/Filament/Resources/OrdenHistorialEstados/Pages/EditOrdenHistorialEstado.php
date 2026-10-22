<?php

namespace App\Filament\Resources\OrdenHistorialEstados\Pages;

use App\Filament\Resources\OrdenHistorialEstados\OrdenHistorialEstadoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdenHistorialEstado extends EditRecord
{
    protected static string $resource = OrdenHistorialEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
