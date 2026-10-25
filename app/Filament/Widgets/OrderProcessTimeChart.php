<?php

namespace App\Filament\Widgets;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\OrdenHistorialEstado;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class OrderProcessTimeChart extends ChartWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 4;

    protected ?string $heading = 'Duración promedio del proceso';

    protected ?string $description = 'Días promedio entre los hitos de aprobación, pago y entrega de las órdenes.';

    protected function getData(): array
    {
        $orders = $this->ordersQuery()
            ->select(['orden_id', 'fecha_registro'])
            ->with(['historialEstados.estado'])
            ->orderByDesc('fecha_registro')
            ->limit(500)
            ->get();

        $stageSeconds = [0, 0, 0, 0];
        $stageCounts = [0, 0, 0, 0];
        $stageCodes = [
            OrdenEstadoCodigo::Aprobada->value,
            OrdenEstadoCodigo::Pagado->value,
            OrdenEstadoCodigo::Verificado->value,
            OrdenEstadoCodigo::Completada->value,
        ];

        foreach ($orders as $order) {
            $events = $order->historialEstados
                ->sortBy('created_at')
                ->values();
            $previousTimestamp = $order->fecha_registro;
            $eventIndex = 0;

            foreach ($stageCodes as $stageIndex => $stageCode) {
                $event = $events->slice($eventIndex)->first(
                    fn (OrdenHistorialEstado $history): bool => $history->estado?->codigo === $stageCode,
                );

                if ($event === null || $previousTimestamp === null) {
                    break;
                }

                $currentTimestamp = CarbonImmutable::parse($event->created_at);
                $previousTimestamp = CarbonImmutable::parse($previousTimestamp);

                if ($currentTimestamp->lessThan($previousTimestamp)) {
                    break;
                }

                $stageSeconds[$stageIndex] += $previousTimestamp->diffInSeconds($currentTimestamp);
                $stageCounts[$stageIndex]++;
                $previousTimestamp = $currentTimestamp;
                $eventIndex = $events->search($event) + 1;
            }
        }

        return [
            'datasets' => [[
                'label' => 'Días promedio',
                'data' => array_map(
                    fn (int $seconds, int $count): float => $count === 0 ? 0 : round($seconds / $count / 86400, 1),
                    $stageSeconds,
                    $stageCounts,
                ),
                'backgroundColor' => '#0f766e',
            ]],
            'labels' => ['Registro → aprobación', 'Aprobación → pago', 'Pago → revisión', 'Revisión → entrega'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'cliente']);
    }
}
