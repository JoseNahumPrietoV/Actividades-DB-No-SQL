<?php

namespace App\Filament\ServicioSocial\Resources\MaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\MaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
