<?php

namespace App\Filament\Resources\PersonaAutorizadas;

use App\Filament\Resources\PersonaAutorizadas\Pages\CreatePersonaAutorizada;
use App\Filament\Resources\PersonaAutorizadas\Pages\EditPersonaAutorizada;
use App\Filament\Resources\PersonaAutorizadas\Pages\ListPersonaAutorizadas;
use App\Mail\CredentialsMail;
use App\Models\PersonaAutorizada;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

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
                // Toggle::make('usado')
                //     ->label('Ya utilizado')
                //     ->disabled()
                //     ->dehydrated(false),
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
                // IconColumn::make('usado')
                //     ->label('Usado')
                //     ->boolean(),
                TextColumn::make('usuario.name')
                    ->label('Usuario creado')
                    ->placeholder('—'),
                // TextColumn::make('usado_en')
                //     ->label('Usado el')
                //     ->dateTime()
                //     ->toggleable(),
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
                            ->label('Usuario')
                            ->required(),

                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->modalHeading('Enviar credenciales')
                    ->modalSubmitActionLabel('Enviar')
                    ->action(function ($record, array $data): void {
                        Mail::to($record->email)->send(
                            new CredentialsMail(
                                username: $data['username'],
                                password: $data['password'],
                            )
                        );
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
