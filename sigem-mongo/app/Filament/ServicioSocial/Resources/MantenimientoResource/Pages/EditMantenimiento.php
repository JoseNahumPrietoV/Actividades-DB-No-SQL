<?php

namespace App\Filament\ServicioSocial\Resources\MantenimientoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\MantenimientoResource;
use Filament\Resources\Pages\EditRecord;

class EditMantenimiento extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = MantenimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return url('/servicio-social');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Registro actualizado exitosamente';
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Registro eliminado exitosamente';
    }
}
