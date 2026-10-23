<?php

namespace App\Filament\Resources\Ordens\Pages;

use App\Filament\Resources\Ordens\OrdenResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrden extends ViewRecord
{
    protected static string $resource = OrdenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
