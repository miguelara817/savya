<?php

namespace App\Filament\Widgets;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\ReservaStock;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardStats extends BaseWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = [];

        if ($this->canSeeSalesData()) {
            $stats = [
                Stat::make('Pendientes de aprobación', $this->ordersQuery()
                    ->whereHas('estado', fn (Builder $query) => $query->where('codigo', OrdenEstadoCodigo::PendienteAprobacion->value))
                    ->count())
                    ->color('warning'),
                Stat::make($this->isClientOnly() ? 'Mis pedidos por pagar' : 'Monto por cobrar', 'Bs '.number_format((float) $this->ordersQuery()
                    ->whereHas('estado', fn (Builder $query) => $query->where('codigo', OrdenEstadoCodigo::Aprobada->value))
                    ->sum('precio_total'), 2, ',', '.'))
                    ->color('success'),
                Stat::make('Pagos por revisar', $this->ordersQuery()
                    ->whereHas('estado', fn (Builder $query) => $query->where('codigo', OrdenEstadoCodigo::Pagado->value))
                    ->count())
                    ->color('danger'),
            ];
        }

        if ($this->canSeeInventoryData()) {
            $availableUnits = $this->stockQuery()
                ->selectRaw('COALESCE(SUM(cantidad_actual - cantidad_reservada), 0) as total_disponible')
                ->value('total_disponible');

            $lowStockCount = $this->stockQuery()
                ->whereRaw('cantidad_actual - cantidad_reservada <= ?', [5])
                ->count();

            $reservedUnits = $this->stockQuery()->sum('cantidad_reservada');
            $activeReservationsQuery = ReservaStock::query()
                ->whereIn('estado', [ReservaStock::ESTADO_ACTIVA, ReservaStock::ESTADO_PARCIAL]);

            if ($warehouseId = $this->selectedWarehouseId()) {
                $activeReservationsQuery->where('almacen_id', $warehouseId);
            }

            $activeReservations = $activeReservationsQuery->count();

            $stats[] = Stat::make('Stock disponible', number_format((int) $availableUnits))
                ->color('success');
            $stats[] = Stat::make('Stock bajo (≤ 5 disponibles)', $lowStockCount)
                ->color($lowStockCount > 0 ? 'danger' : 'success');
            $stats[] = Stat::make('Unidades reservadas', number_format((int) $reservedUnits))
                ->color('info');
            $stats[] = Stat::make('Reservas activas', $activeReservations)
                ->color('info');
        }

        if ($this->canSeeDeliveryData()) {
            $stats[] = Stat::make('Entregas pendientes', $this->ordersQuery()
                ->whereHas('estado', fn (Builder $query) => $query->whereIn('codigo', [
                    OrdenEstadoCodigo::Verificado->value,
                    OrdenEstadoCodigo::ParcialmenteAtendida->value,
                ]))
                ->count())
                ->color('warning');
        }

        if ($this->isClientOnly()) {
            $stats[] = Stat::make('Mis pedidos activos', $this->ordersQuery()
                ->whereHas('estado', fn (Builder $query) => $query->whereIn('codigo', [
                    OrdenEstadoCodigo::Aprobada->value,
                    OrdenEstadoCodigo::Pagado->value,
                    OrdenEstadoCodigo::Verificado->value,
                    OrdenEstadoCodigo::ParcialmenteAtendida->value,
                ]))
                ->count())
                ->color('primary');
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole([
                'super_admin', 'administrador', 'encargado_ventas', 'encargado_almacen',
                'responsable_inventario', 'cliente', 'cliente_ad'
            ]);
    }
}
