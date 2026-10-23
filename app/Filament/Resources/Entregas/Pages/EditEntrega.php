<?php

namespace App\Filament\Resources\Entregas\Pages;

use App\Filament\Resources\Entregas\EntregaResource;
use App\Models\Entrega;
use App\Models\User;
use App\Services\EntregaService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditEntrega extends EditRecord
{
    protected static string $resource = EntregaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('confirmar')
                ->label('Confirmar entrega')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar entrega')
                ->modalDescription('Se registrará la conformidad del receptor. No se descuenta stock nuevamente.')
                ->visible(fn (): bool => $this->getRecord()->estado === Entrega::ESTADO_BORRADOR)
                ->authorize(fn (): bool => Filament::auth()->user()?->can('confirmar', $this->getRecord()) ?? false)
                ->action(function (): void {
                    $usuario = Filament::auth()->user();

                    if (! $usuario instanceof User) {
                        abort(403);
                    }

                    try {
                        app(EntregaService::class)->confirmar($this->getRecord(), $usuario);
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('No se pudo confirmar la entrega')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Entrega confirmada')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'estado',
                        'fecha_entrega',
                        'firmado_en',
                        'confirmado_en',
                        'responsable_user_id',
                    ]);
                }),
            DeleteAction::make(),
        ];
    }
}
