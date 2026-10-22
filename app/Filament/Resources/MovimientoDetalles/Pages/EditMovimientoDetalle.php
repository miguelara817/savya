<?php

namespace App\Filament\Resources\MovimientoDetalles\Pages;

use App\Filament\Resources\MovimientoDetalles\MovimientoDetalleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMovimientoDetalle extends EditRecord
{
    protected static string $resource = MovimientoDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
