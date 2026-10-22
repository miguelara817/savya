<?php

namespace App\Filament\Resources\SalidaDetalles\Pages;

use App\Filament\Resources\SalidaDetalles\SalidaDetalleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalidaDetalle extends EditRecord
{
    protected static string $resource = SalidaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
