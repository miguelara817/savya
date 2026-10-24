<?php

namespace App\Filament\Resources\PublicacionExistencias\Pages;

use App\Filament\Resources\PublicacionExistencias\PublicacionExistenciaResource;
use App\Models\PublicacionExistencia;
use App\Models\User;
use App\Services\PublicacionExistenciaService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditPublicacionExistencia extends EditRecord
{
    protected static string $resource = PublicacionExistenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('publicar')
                ->label('Confirmar ingreso')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar ingreso de inventario')
                ->modalDescription('Se sumarán las cantidades al stock físico del producto y almacén, y se actualizará el precio para nuevas órdenes. Las reservas y los precios de órdenes existentes se conservarán.')
                ->visible(fn (): bool => $this->getRecord()->estado === PublicacionExistencia::ESTADO_BORRADOR)
                ->authorize(fn (): bool => Filament::auth()->user()?->can('publicar', $this->getRecord()) ?? false)
                ->action(function (): void {
                    $usuario = Filament::auth()->user();

                    if (! $usuario instanceof User) {
                        abort(403);
                    }

                    try {
                        app(PublicacionExistenciaService::class)->confirmarIngreso($this->getRecord(), $usuario);
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('No se pudo confirmar el ingreso')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Ingreso confirmado')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'estado',
                        'publicada_en',
                        'responsable_user_id',
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
