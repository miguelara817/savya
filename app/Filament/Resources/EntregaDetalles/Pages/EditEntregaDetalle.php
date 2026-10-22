<?php

namespace App\Filament\Resources\EntregaDetalles\Pages;

use App\Filament\Resources\EntregaDetalles\EntregaDetalleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntregaDetalle extends EditRecord
{
    protected static string $resource = EntregaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
