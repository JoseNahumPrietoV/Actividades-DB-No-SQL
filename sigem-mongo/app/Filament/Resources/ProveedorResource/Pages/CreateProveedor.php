<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\ProveedorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = ProveedorResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
