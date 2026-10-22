<?php

namespace App\Filament\Resources\PublicacionExistencias\Pages;

use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPublicacionExistencia extends EditRecord
{
    protected static string $resource = PublicacionExistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
