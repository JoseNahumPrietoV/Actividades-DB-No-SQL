<?php

namespace App\Filament\ServicioSocial\Resources\AreaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\AreaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = AreaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
