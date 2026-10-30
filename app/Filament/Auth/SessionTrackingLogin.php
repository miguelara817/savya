<?php

namespace App\Filament\Auth;

use App\Models\User;
use App\Models\UserSession;
use Filament\Auth\Http\Responses\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;

// use Illuminate\Support\Facades\Filament;

class SessionTrackingLogin extends Login
{
    public function authenticate(): ?LoginResponse
    {
        $response = parent::authenticate();

        $user = Filament::auth()->user();

        if ($response && $user instanceof User && request()->hasSession()) {
            UserSession::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'logged_in_at' => now(),
                ],
                [
                    'ip_address' => request()->ip(),
                    'logged_out_at' => null,
                ],
            );
        }

        return $response;
    }
}
