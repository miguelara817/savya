<?php

use App\Models\PersonaAutorizada;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

new class extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->autocomplete('email'),
                TextInput::make('carnet')
                    ->label('Carnet de identidad')
                    ->required(),
                TextInput::make('nombre')
                    ->label('Nombres')
                    ->required(),
                TextInput::make('apellido')
                    ->label('Apellidos')
                    ->required(),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->required(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('Confirmar contraseña')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function registrar(): void
    {
        $data = $this->form->getState();

        $autorizado = PersonaAutorizada::query()
            ->where('correo', $data['correo'])
            ->where('carnet', $data['carnet'])
            ->where('usado', false)
            ->first();

        if (! $autorizado) {
            Notification::make()
                ->title('Registro no autorizado')
                ->body('Sus datos no se encuentran en la lista de personas autorizadas o ya fueron utilizados.')
                ->danger()
                ->send();

            return;
        }

        if (User::query()->where('email', $data['correo'])->exists()) {
            Notification::make()
                ->title('Correo ya registrado')
                ->body('Ya existe una cuenta con este correo electrónico.')
                ->danger()
                ->send();

            return;
        }

        $usuario = User::query()->create([
            'name' => trim($autorizado->nombre.' '.$autorizado->apellido),
            'email' => $autorizado->correo,
            'password' => Hash::make($data['password']),
        ]);

        $usuario->assignRole('Cliente');

        $autorizado->update([
            'usado' => true,
            'usado_en' => now(),
            'user_id' => $usuario->id,
        ]);

        Auth::login($usuario);

        $this->redirectIntended('/admin');
    }

    public function render()
    {
        return view('components.auth-register-autorizado');
    }
};
?>

<div class="w-full max-w-md mx-auto mt-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
            Registro de usuario autorizado
        </h1>
    </div>

    <form wire:submit="registrar" class="fi-form grid gap-6">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Registrarse
        </x-filament::button>

        <div class="text-center">
            <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-primary-600 hover:underline">
                Ya tengo cuenta, iniciar sesión
            </a>
        </div>
    </form>

    <x-filament-actions::modals />
</div>
