<?php

namespace App\Filament\Resources\PersonaAutorizadas;

use App\Filament\Resources\PersonaAutorizadas\Pages\CreatePersonaAutorizada;
use App\Filament\Resources\PersonaAutorizadas\Pages\EditPersonaAutorizada;
use App\Filament\Resources\PersonaAutorizadas\Pages\ListPersonaAutorizadas;
use App\Models\PersonaAutorizada;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class PersonaAutorizadaResource extends Resource
{
    protected static ?string $model = PersonaAutorizada::class;

    protected static ?string $slug = 'personas-autorizadas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 90;

    protected static ?string $modelLabel = 'persona autorizada';

    protected static ?string $pluralModelLabel = 'personas autorizadas';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->label('Nombres')
                    ->required()
                    ->maxLength(100),
                TextInput::make('apellido')
                    ->label('Apellidos')
                    ->required()
                    ->maxLength(100),
                TextInput::make('carnet')
                    ->label('Carnet de identidad')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                TextInput::make('correo')
                    ->label('Correo electrónico')
                    ->email()
                    ->required()
                    ->maxLength(150)
                    ->unique(ignoreRecord: true),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->maxLength(30),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')
                    ->label('Nombres')
                    ->searchable(),
                TextColumn::make('apellido')
                    ->label('Apellidos')
                    ->searchable(),
                TextColumn::make('carnet')
                    ->label('Carnet')
                    ->searchable(),
                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable(),
                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->toggleable(),
                TextColumn::make('usuario.name')
                    ->label('Usuario creado')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                Action::make('sendCredentials')
                    ->label('Enviar credenciales')
                    ->icon('heroicon-o-envelope')
                    ->color('success')
                    ->form([
                        TextInput::make('username')
                            ->label('Usuario / Correo')
                            ->required()
                            ->default(fn (PersonaAutorizada $record): string => $record->correo),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(6),
                    ])
                    ->modalHeading('Enviar credenciales')
                    ->modalSubmitActionLabel('Enviar')
                    ->action(function (PersonaAutorizada $record, array $data): void {
                        $usuario = User::query()->firstOrNew(['email' => $record->correo]);

                        $usuario->name = $usuario->name ?: trim($record->nombre.' '.$record->apellido);
                        $usuario->email = $data['username'] ?: $record->correo;
                        $usuario->password = Hash::make($data['password']);
                        $usuario->save();

                        if (method_exists($usuario, 'assignRole')) {
                            $usuario->assignRole('cliente');
                        }

                        $record->update([
                            'user_id' => $usuario->id,
                        ]);

                        Notification::make()
                            ->title('Credenciales creadas/actualizadas')
                            ->body('Se creó o actualizó el usuario con rol Cliente.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonaAutorizadas::route('/'),
            // 'create' => CreatePersonaAutorizada::route('/create'),
            // 'edit' => EditPersonaAutorizada::route('/{record}/edit'),
        ];
    }
}
