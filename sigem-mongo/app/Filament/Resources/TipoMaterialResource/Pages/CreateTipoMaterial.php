<?php

namespace App\Filament\Resources\TipoMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\TipoMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTipoMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = TipoMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
