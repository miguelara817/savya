<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AuthServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AuthServiceProvider::class,
];
