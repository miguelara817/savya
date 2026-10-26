<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class StockByWarehouseChart extends ChartWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 5;

    protected ?string $heading = 'Stock disponible por almacén';

    protected ?string $description = 'Stock físico menos las unidades reservadas.';

    protected function getData(): array
    {
        $saldos = $this->stockQuery()
            ->toBase()
            ->join('almacenes', 'almacenes.almacen_id', '=', 'stock_saldos.almacen_id')
            ->select('almacenes.nombre')
            ->selectRaw('SUM(stock_saldos.cantidad_actual - stock_saldos.cantidad_reservada) as disponible')
            ->groupBy('almacenes.almacen_id', 'almacenes.nombre')
            ->orderBy('almacenes.nombre')
            ->get();

        return [
            'datasets' => [[
                'label' => 'Unidades disponibles',
                'data' => $saldos->pluck('disponible')->map(fn (mixed $cantidad): int => (int) $cantidad)->all(),
                'backgroundColor' => '#2563eb',
            ]],
            'labels' => $saldos->pluck('nombre')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(['super_admin', 'administrador', 'responsable_inventario']);
    }
}
