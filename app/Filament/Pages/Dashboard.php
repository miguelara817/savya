<?php

namespace App\Filament\Pages;

use App\Models\Almacen;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('periodo')
                    ->label('Período')
                    ->options([
                        '30_dias' => 'Últimos 30 días',
                        '90_dias' => 'Últimos 90 días',
                        'anio' => 'Año actual',
                        'todo' => 'Todo el historial',
                    ])
                    ->default('30_dias'),
                Select::make('almacen_id')
                    ->label('Almacén')
                    ->options(fn (): array => Almacen::query()
                        ->where('is_active', true)
                        ->orderBy('nombre')
                        ->pluck('nombre', 'almacen_id')
                        ->all())
                    ->placeholder('Todos los almacenes')
                    ->searchable(),
            ])
            ->columns(2);
    }
}
