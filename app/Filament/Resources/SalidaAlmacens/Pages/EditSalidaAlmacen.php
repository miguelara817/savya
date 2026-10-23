<?php

namespace App\Filament\Resources\SalidaAlmacens\Pages;

use App\Filament\Resources\SalidaAlmacens\SalidaAlmacenResource;
use App\Models\SalidaAlmacen;
use App\Models\User;
use App\Services\SalidaAlmacenService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditSalidaAlmacen extends EditRecord
{
    protected static string $resource = SalidaAlmacenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmar')
                ->label('Confirmar salida')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar salida de almacén')
                ->modalDescription('Se descontará el stock físico y se liberará la reserva utilizada. Esta acción es irreversible.')
                ->visible(fn (): bool => $this->getRecord()->estado === SalidaAlmacen::ESTADO_BORRADOR)
                ->authorize(fn (): bool => Filament::auth()->user()?->can('confirmar', $this->getRecord()) ?? false)
                ->action(function (): void {
                    $usuario = Filament::auth()->user();

                    if (! $usuario instanceof User) {
                        abort(403);
                    }

                    try {
                        app(SalidaAlmacenService::class)->confirmar($this->getRecord(), $usuario);
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('No se pudo confirmar la salida')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Salida confirmada')
                        ->body('El stock físico y las reservas fueron actualizados.')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'estado',
                        'fecha_salida',
                        'firmado_en',
                        'confirmado_en',
                        'responsable_user_id',
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
