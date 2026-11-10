<?php

namespace App\Filament\ServicioSocial\Resources\ReceptorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\ReceptorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceptor extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = ReceptorResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
