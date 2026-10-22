<?php

namespace App\Filament\Resources\StockSaldos\Pages;

use App\Filament\Resources\StockSaldos\StockSaldoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockSaldo extends EditRecord
{
    protected static string $resource = StockSaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
