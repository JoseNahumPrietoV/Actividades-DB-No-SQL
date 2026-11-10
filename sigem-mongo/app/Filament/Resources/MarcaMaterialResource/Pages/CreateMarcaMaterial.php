<?php

namespace App\Filament\Resources\MarcaMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\MarcaMaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarcaMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MarcaMaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
