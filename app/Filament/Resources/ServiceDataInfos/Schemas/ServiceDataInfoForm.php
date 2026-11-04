<?php

namespace App\Filament\Resources\ServiceDataInfos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceDataInfoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('service')
                    ->label('Nombre del servicio')
                    ->required(),
                TextInput::make('base_url')
                    ->label('URL Base')
                    ->url()
                    ->required(),
                TextInput::make('api_key_header')
                    ->label('Prefijo de API KEY')
                    ->required(),
                TextInput::make('api_key')
                    ->label('API KEY')
                    ->required(),
                TextInput::make('session_id')
                    ->label('ID de sesión')
                    ->required(),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
