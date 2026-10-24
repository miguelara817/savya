<?php

namespace App\Filament\Resources\Ordens\Pages;

use App\Filament\Resources\Ordens\OrdenResource;
use App\Support\DocumentoNumero;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateOrden extends CreateRecord
{
    protected static string $resource = OrdenResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['codigo_orden'] = $data['codigo_orden'] ?? (string) Str::uuid();
        $data['numero'] = $data['numero'] ?? DocumentoNumero::generar('ORD');
        $data['solicitante_user_id'] = $data['solicitante_user_id'] ?? Filament::auth()->id();
        $data['orden_estado_id'] = OrdenResource::estadoInicialId();
        $data['fecha_registro'] = $data['fecha_registro'] ?? now();

        return $data;
    }
}
