<?php

namespace App\Filament\Resources\ReservaStocks\Pages;

use App\Filament\Resources\ReservaStocks\ReservaStockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReservaStock extends EditRecord
{
    protected static string $resource = ReservaStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
