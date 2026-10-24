<?php

namespace App\Filament\Resources\PublicacionExistencias\Pages;

use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use App\Models\PublicacionExistencia;
use App\Support\DocumentoNumero;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListPublicacionExistencias extends ListRecords
{
    protected static string $resource = PublicacionExistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva carga inicial')
                ->databaseTransaction()
                ->mutateDataUsing(function (array $data): array {
                    $data['numero'] = DocumentoNumero::generar('PUB');
                    $data['responsable_user_id'] = Filament::auth()->id();
                    $data['estado'] = PublicacionExistencia::ESTADO_BORRADOR;

                    return $data;
                }),
        ];
    }
}
