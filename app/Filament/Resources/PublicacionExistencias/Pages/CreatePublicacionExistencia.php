<?php

namespace App\Filament\Resources\PublicacionExistencias\Pages;

use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use App\Models\PublicacionExistencia;
use App\Models\User;
use App\Services\PublicacionExistenciaService;
use App\Support\DocumentoNumero;
use DomainException;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;

class CreatePublicacionExistencia extends CreateRecord
{
    protected static string $resource = PublicacionExistenciaResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['numero'] = $data['numero'] ?? DocumentoNumero::generar('PUB');
        $data['responsable_user_id'] = $data['responsable_user_id'] ?? Filament::auth()->id();
        $data['estado'] = $data['estado'] ?? PublicacionExistencia::ESTADO_BORRADOR;
        $data['fecha_publicacion'] = $data['fecha_publicacion'] ?? now();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicar')
                ->label('Publicar')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()?->estado === PublicacionExistencia::ESTADO_BORRADOR)
                ->action(function (): void {
                    $usuario = Filament::auth()->user();

                    if (! $usuario instanceof User) {
                        abort(403);
                    }

                    try {
                        app(PublicacionExistenciaService::class)->publicar($this->getRecord(), $usuario);
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('No se pudo publicar')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Publicación registrada')
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->getRecord()]));
                }),
        ];
    }
}
