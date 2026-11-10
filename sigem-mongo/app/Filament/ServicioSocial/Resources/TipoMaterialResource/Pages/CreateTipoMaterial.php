<?php

namespace App\Filament\ServicioSocial\Resources\TipoMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\TipoMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = TipoMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
