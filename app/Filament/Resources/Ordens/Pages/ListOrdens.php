<?php

namespace App\Filament\Resources\Ordens\Pages;

use App\Enums\OrdenEstadoCodigo;
use App\Filament\Resources\Ordens\OrdenResource;
use App\Models\OrdenEstado;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListOrdens extends ListRecords
{
    protected static string $resource = OrdenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalHeading('Crear orden')
                // ->modalWidth('7xl')
                ->createAnother(false)
                ->modalSubmitActionLabel('Guardar')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['codigo_orden'] = $data['codigo_orden'] ?? (string) Str::uuid();
                    $data['solicitante_user_id'] = $data['solicitante_user_id'] ?? Filament::auth()->id();
                    $data['orden_estado_id'] = $data['orden_estado_id'] ?? OrdenEstado::query()
                        ->where('codigo', OrdenEstadoCodigo::PendienteAprobacion->value)
                        ->value('orden_estado_id') ?? OrdenResource::estadoInicialId();
                    $data['fecha_registro'] = $data['fecha_registro'] ?? now();
                    $data['numero'] = $data['numero'] ?? null;

                    $productos = collect($data['productos'] ?? []);

                    $data['cantidad_total'] = $productos->sum(
                        fn (array $item): int => (int) ($item['cantidad_solicitada'] ?? 0)
                    );

                    $data['precio_total'] = $productos->sum(
                        fn (array $item): float => (float) ($item['cantidad_solicitada'] ?? 0)
                            * (float) ($item['precio_unitario'] ?? 0)
                    );

                    return $data;
                }),
        ];
    }
}
