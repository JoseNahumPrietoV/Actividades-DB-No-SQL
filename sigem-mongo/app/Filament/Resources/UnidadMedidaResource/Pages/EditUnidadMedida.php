<?php

namespace App\Filament\Resources\UnidadMedidaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\UnidadMedidaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnidadMedida extends EditRecord
{
    public static ?string $title = 'Editar';

    use ConDashboardBreadcrumb;

    protected static string $resource = UnidadMedidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return url('/admin');
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
