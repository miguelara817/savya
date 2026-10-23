<?php

namespace App\Providers\Filament;

use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    // public function boot(): void
    // {
    //     FilamentView::registerRenderHook(
    //         PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
    //         function (): string {
    //             $url = route('registro.autorizado');

    //             return Blade::render(
    //                 '<div class="fi-simple-footer mt-6 text-center text-sm text-gray-600 dark:text-gray-400">¿Eres cliente autorizado? <a href="'.$url.'" class="fi-link text-primary-600 hover:underline dark:text-primary-400">Regístrate aquí</a></div>',
    //             );
    //         },
    //     );
    // }
}
