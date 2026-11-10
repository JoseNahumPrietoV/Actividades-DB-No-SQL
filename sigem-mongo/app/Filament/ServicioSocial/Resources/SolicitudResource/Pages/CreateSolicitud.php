<?php

namespace App\Filament\ServicioSocial\Resources\SolicitudResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\SolicitudResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSolicitud extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = SolicitudResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
