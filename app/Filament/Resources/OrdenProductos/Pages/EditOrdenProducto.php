<?php

namespace App\Filament\Resources\OrdenProductos\Pages;

use App\Filament\Resources\OrdenProductos\OrdenProductoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrdenProducto extends EditRecord
{
    protected static string $resource = OrdenProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
