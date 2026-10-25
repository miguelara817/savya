<?php

namespace App\Filament\Widgets;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\Orden;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class OrderValueChart extends ChartWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 3;

    protected ?string $heading = 'Ventas confirmadas por mes';

    protected ?string $description = 'Importe de las órdenes completadas durante el período seleccionado.';

    protected function getData(): array
    {
        $query = Orden::query()
            ->join('orden_historial_estados', 'orden_historial_estados.orden_id', '=', 'ordenes.orden_id')
            ->join('orden_estados', 'orden_estados.orden_estado_id', '=', 'orden_historial_estados.orden_estado_id')
            ->where('orden_estados.codigo', OrdenEstadoCodigo::Completada->value);

        if ($warehouseId = $this->selectedWarehouseId()) {
            $query->where('ordenes.almacen_id', $warehouseId);
        }

        if ($startDate = $this->periodStartDate()) {
            $query->where('orden_historial_estados.created_at', '>=', $startDate);
        }

        if ($this->isClientOnly()) {
            $query->where('ordenes.solicitante_user_id', auth()->id());
        }

        $totalesPorDia = $query
            ->selectRaw('DATE(orden_historial_estados.created_at) as fecha, SUM(ordenes.precio_total) as total')
            ->groupByRaw('DATE(orden_historial_estados.created_at)')
            ->orderBy('fecha')
            ->pluck('total', 'fecha');

        $totalesPorMes = [];

        foreach ($totalesPorDia as $fecha => $total) {
            $mes = date('Y-m', strtotime((string) $fecha));
            $totalesPorMes[$mes] = ($totalesPorMes[$mes] ?? 0) + (float) $total;
        }

        if (($this->pageFilters['periodo'] ?? '30_dias') === 'todo' && $totalesPorMes !== []) {
            $primerMes = CarbonImmutable::createFromFormat('!Y-m', min(array_keys($totalesPorMes)));
            $ultimoMes = now()->startOfMonth()->toImmutable();
            $meses = collect();

            while ($primerMes->lessThanOrEqualTo($ultimoMes)) {
                $meses->push($primerMes->format('Y-m'));
                $primerMes = $primerMes->addMonth();
            }
        } else {
            $cantidadMeses = match ($this->pageFilters['periodo'] ?? '30_dias') {
                '30_dias' => 2,
                '90_dias' => 4,
                default => 12,
            };
            $meses = collect(range($cantidadMeses - 1, 0))
                ->map(fn (int $mes): string => now()->startOfMonth()->subMonths($mes)->format('Y-m'));
        }

        return [
            'datasets' => [[
                'label' => 'Total (Bs)',
                'data' => $meses->map(fn (string $mes): float => round($totalesPorMes[$mes] ?? 0, 2))->all(),
                'borderColor' => '#0f766e',
                'backgroundColor' => 'rgba(15, 118, 110, 0.18)',
                'fill' => true,
            ]],
            'labels' => $meses->map(fn (string $mes): string => CarbonImmutable::createFromFormat('!Y-m', $mes)->translatedFormat('M Y'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'cliente']);
    }
}
