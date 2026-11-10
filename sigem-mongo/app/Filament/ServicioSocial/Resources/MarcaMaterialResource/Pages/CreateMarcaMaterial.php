<?php

namespace App\Filament\ServicioSocial\Resources\MarcaMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\MarcaMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarcaMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MarcaMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
