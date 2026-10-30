<?php

namespace App\Filament\Resources\UserSessions;

use App\Filament\Resources\UserSessions\Pages\ListUserSessions;
use App\Models\User;
use App\Models\UserSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserSessionResource extends Resource
{
    protected static ?string $model = UserSession::class;

    protected static ?string $slug = 'user-sessions';

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $modelLabel = 'sesión de usuario';

    protected static ?string $pluralModelLabel = 'sesiones de usuario';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static string|\UnitEnum|null $navigationGroup = 'Administración';

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->hasAnyRole(['super_admin', 'administrador']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('user'))
            ->paginated([25, 50, 100])
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user_id')
                    ->label('ID usuario')
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('Dirección IP')
                    ->placeholder('No disponible'),
                TextColumn::make('logged_in_at')
                    ->label('Conexión')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('logged_out_at')
                    ->label('Desconexión')
                    ->dateTime()
                    ->placeholder('Sin cierre registrado')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Usuario')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('logged_in_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserSessions::route('/'),
        ];
    }
}
