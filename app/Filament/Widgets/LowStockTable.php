<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\StockSaldos\StockSaldoResource;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\StockSaldo;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockTable extends TableWidget
{
    use InteractsWithDashboardData;

    protected static ?string $heading = 'Stock bajo';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->stockQuery()
                ->with(['producto', 'almacen'])
                ->whereRaw('cantidad_actual - cantidad_reservada <= ?', [5])
                ->orderByRaw('(cantidad_actual - cantidad_reservada) asc'))
            ->columns([
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable(),
                TextColumn::make('almacen.nombre')
                    ->label('Almacén'),
                TextColumn::make('cantidad_actual')
                    ->label('Físico')
                    ->numeric(),
                TextColumn::make('cantidad_reservada')
                    ->label('Reservado')
                    ->numeric(),
                TextColumn::make('disponible')
                    ->label('Disponible')
                    ->numeric()
                    ->color('danger'),
            ])
            ->recordUrl(fn (StockSaldo $record): string => StockSaldoResource::getUrl('index'))
            ->paginated([5, 10]);
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['super_admin', 'administrador', 'responsable_inventario']);
    }
}
