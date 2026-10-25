<?php

namespace App\Filament\Widgets;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Resources\Ordens\OrdenResource;
use App\Filament\Widgets\Concerns\InteractsWithDashboardData;
use App\Models\Orden;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class AttentionOrdersTable extends TableWidget
{
    use InteractsWithDashboardData;

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->attentionOrdersQuery())
            ->columns([
                TextColumn::make('numero')
                    ->label('Orden')
                    ->searchable(),
                TextColumn::make('razon_social')
                    ->label('Cliente')
                    ->limit(30),
                TextColumn::make('estado.nombre')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('precio_total')
                    ->label('Total')
                    ->money('BOB'),
                TextColumn::make('fecha_registro')
                    ->label('Registro')
                    ->date(),
            ])
            ->recordUrl(fn (Orden $record): string => OrdenResource::getUrl('view', ['record' => $record]))
            ->defaultSort('fecha_registro', 'asc')
            ->paginated([5, 10]);
    }

    protected function attentionOrdersQuery(): Builder
    {
        $user = auth()->user();

        $states = match (true) {
            $this->isClientOnly() => [OrdenEstadoCodigo::Aprobada->value],
            $user instanceof User && $user->hasAnyRole(['super_admin', 'administrador']) => [
                OrdenEstadoCodigo::PendienteAprobacion->value,
                OrdenEstadoCodigo::Pagado->value,
                OrdenEstadoCodigo::Verificado->value,
                OrdenEstadoCodigo::ParcialmenteAtendida->value,
            ],
            $user instanceof User && $user->hasRole('encargado_almacen') => [
                OrdenEstadoCodigo::Verificado->value,
                OrdenEstadoCodigo::ParcialmenteAtendida->value,
            ],
            default => [
                OrdenEstadoCodigo::PendienteAprobacion->value,
                OrdenEstadoCodigo::Pagado->value,
            ],
        };

        return $this->ordersQuery()
            ->with('estado')
            ->whereHas('estado', fn (Builder $query) => $query->whereIn('codigo', $states));
    }

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasAnyRole(['super_admin', 'administrador', 'encargado_ventas', 'encargado_almacen', 'cliente']);
    }
}
