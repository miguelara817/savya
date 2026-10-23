<?php

namespace App\Filament\Resources\ReservaStocks\Pages;

use App\Filament\Resources\ReservaStocks\ReservaStockResource;
use Filament\Resources\Pages\ListRecords;

class ListReservaStocks extends ListRecords
{
    protected static string $resource = ReservaStockResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
