<?php

namespace App\Filament\Auth;

use Filament\Auth\MultiFactor\Email\EmailAuthentication as BaseEmailAuthentication;
use Filament\Forms\Components\OneTimeCodeInput;
use Illuminate\Contracts\Auth\Authenticatable;

class EmailAuthentication extends BaseEmailAuthentication
{
    public function getChallengeFormComponents(Authenticatable $user): array
    {
        $components = parent::getChallengeFormComponents($user);

        foreach ($components as $component) {
            if ($component instanceof OneTimeCodeInput) {
                $component->submitOnCompletion(false);
            }
        }

        return $components;
    }
}
