<?php

namespace App\Filament\Resources\ServiceDataInfos\Pages;

use App\Filament\Resources\ServiceDataInfos\ServiceDataInfoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceDataInfo extends EditRecord
{
    protected static string $resource = ServiceDataInfoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
