<?php

namespace App\Filament\Resources\Entregas\Pages;

use App\Filament\Resources\Entregas\EntregaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEntrega extends EditRecord
{
    protected static string $resource = EntregaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
