<?php

namespace App\Filament\Resources\SalidaAlmacens\Pages;

use App\Filament\Resources\SalidaAlmacens\SalidaAlmacenResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalidaAlmacen extends ViewRecord
{
    protected static string $resource = SalidaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
