<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class TopProductsChart extends ChartWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 6;

    protected ?string $heading = 'Productos con más movimiento';

    protected ?string $description = 'Unidades despachadas en salidas confirmadas durante el período seleccionado.';

    protected function getData(): array
    {
        $query = SalidaDetalle::query()
            ->join('salidas_almacen', 'salidas_almacen.salida_id', '=', 'salida_detalles.salida_id')
            ->join('orden_productos', 'orden_productos.orden_producto_id', '=', 'salida_detalles.orden_producto_id')
            ->join('productos', 'productos.producto_id', '=', 'orden_productos.producto_id')
            ->join('ordenes', 'ordenes.orden_id', '=', 'orden_productos.orden_id')
            ->where('salidas_almacen.estado', SalidaAlmacen::ESTADO_CONFIRMADA)
            ->whereNull('ordenes.deleted_at');

        if ($warehouseId = $this->selectedWarehouseId()) {
            $query->where('ordenes.almacen_id', $warehouseId);
        }

        if ($startDate = $this->periodStartDate()) {
            $query->where('salidas_almacen.confirmado_en', '>=', $startDate);
        }

        if ($this->isClientOnly()) {
            $query->where('ordenes.solicitante_user_id', auth()->id());
        }

        $productos = $query
            ->select('productos.nombre')
            ->selectRaw('SUM(salida_detalles.cantidad_despachada) as unidades')
            ->groupBy('productos.producto_id', 'productos.nombre')
            ->orderByDesc('unidades')
            ->limit(7)
            ->get();

        return [
            'datasets' => [[
                'label' => 'Unidades',
                'data' => $productos->pluck('unidades')->map(fn (mixed $cantidad): int => (int) $cantidad)->all(),
                'backgroundColor' => '#7c3aed',
            ]],
            'labels' => $productos->pluck('nombre')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole([
            'super_admin', 'administrador', 'encargado_ventas', 'encargado_almacen', 'responsable_inventario', 'cliente', 'cliente_ad',
        ]);
    }
}
