<?php

namespace App\Filament\Widgets\Concerns;

use App\Models\Almacen;
use App\Models\Orden;
use App\Models\StockSaldo;
use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithDashboardData
{
    use InteractsWithPageFilters;

    protected function ordersQuery(): Builder
    {
        $query = Orden::query();
        $user = auth()->user();

        if ($this->isClientOnly($user)) {
            $query->where('solicitante_user_id', $user->getAuthIdentifier());
        }

        if ($warehouseId = $this->selectedWarehouseId()) {
            $query->where('almacen_id', $warehouseId);
        }

        if ($startDate = $this->periodStartDate()) {
            $query->where('fecha_registro', '>=', $startDate);
        }

        return $query;
    }

    protected function stockQuery(): Builder
    {
        $query = StockSaldo::query();

        if ($warehouseId = $this->selectedWarehouseId()) {
            $query->where('stock_saldos.almacen_id', $warehouseId);
        }

        return $query;
    }

    protected function selectedWarehouseId(): ?int
    {
        $warehouseId = filter_var($this->pageFilters['almacen_id'] ?? null, FILTER_VALIDATE_INT);

        if (! $warehouseId || ! Almacen::query()->whereKey($warehouseId)->exists()) {
            return null;
        }

        return $warehouseId;
    }

    protected function periodStartDate(): ?CarbonImmutable
    {
        $today = CarbonImmutable::now()->startOfDay();

        return match ($this->pageFilters['periodo'] ?? '30_dias') {
            '30_dias' => $today->subDays(29),
            '90_dias' => $today->subDays(89),
            'anio' => $today->startOfYear(),
            'todo' => null,
            default => $today->subDays(29),
        };
    }

    protected function isClientOnly(?object $user = null): bool
    {
        $user ??= auth()->user();

        return $user instanceof User
            && $user->hasRole('cliente')
            && ! $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'encargado_almacen', 'responsable_inventario']);
    }

    protected function canSeeSalesData(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'cliente']);
    }

    protected function canSeeInventoryData(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['super_admin', 'administrador', 'responsable_inventario']);
    }

    protected function canSeeDeliveryData(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_almacen']);
    }
}
