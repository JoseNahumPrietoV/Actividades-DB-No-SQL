<?php

namespace App\Filament\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\MantenimientoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMantenimiento extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MantenimientoResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
