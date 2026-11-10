<?php

namespace App\Filament\ServicioSocial\Resources\MarcaMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\MarcaMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMarcaMaterial extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = MarcaMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
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
