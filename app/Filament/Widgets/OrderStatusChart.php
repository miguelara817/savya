<?php

namespace App\Filament\Widgets;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\OrdenEstado;
use App\Models\User;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Pedidos por estado';

    protected ?string $description = 'Distribución de pedidos del período seleccionado.';

    protected function getData(): array
    {
        $codigosEstadoActivos = array_map(
            static fn (OrdenEstadoCodigo $estado): string => $estado->value,
            OrdenEstadoCodigo::cases(),
        );

        $estados = OrdenEstado::query()
            ->whereIn('codigo', $codigosEstadoActivos)
            ->orderBy('orden_estado_id')
            ->get();
        $conteos = $this->ordersQuery()
            ->selectRaw('orden_estado_id, COUNT(*) as total')
            ->groupBy('orden_estado_id')
            ->pluck('total', 'orden_estado_id');

        return [
            'datasets' => [[
                'label' => 'Órdenes',
                'data' => $estados->map(fn (OrdenEstado $estado): int => (int) $conteos->get($estado->orden_estado_id, 0))->all(),
                'backgroundColor' => ['#0f766e', '#f59e0b', '#16a34a', '#2563eb', '#7c3aed', '#ea580c', '#15803d', '#dc2626', '#64748b'],
            ]],
            'labels' => $estados->pluck('nombre')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'cliente', 'cliente_ad']);
    }
}
