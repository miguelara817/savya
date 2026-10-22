<?php

namespace App\Filament\Resources\MovimientoHistorialEstados\Pages;

use App\Filament\Resources\MovimientoHistorialEstados\MovimientoHistorialEstadoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMovimientoHistorialEstado extends EditRecord
{
    protected static string $resource = MovimientoHistorialEstadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
