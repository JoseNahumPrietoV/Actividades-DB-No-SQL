<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ServicioSocialPanelProvider;
use Spatie\Permission\PermissionServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ServicioSocialPanelProvider::class,
    PermissionServiceProvider::class,
];
