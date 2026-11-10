<?php

namespace App\Filament\ServicioSocial\Resources\UnidadMedidaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\UnidadMedidaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUnidadMedida extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = UnidadMedidaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
