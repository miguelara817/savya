<?php

namespace App\Filament\Resources\SalidaAlmacens\Pages;

use App\Filament\Resources\SalidaAlmacens\SalidaAlmacenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSalidaAlmacen extends EditRecord
{
    protected static string $resource = SalidaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
