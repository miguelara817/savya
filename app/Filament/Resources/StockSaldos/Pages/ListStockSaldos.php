<?php

namespace App\Filament\Resources\StockSaldos\Pages;

use App\Filament\Resources\StockSaldos\StockSaldoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStockSaldos extends ListRecords
{
    protected static string $resource = StockSaldoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
