<?php

namespace App\Filament\Resources\MaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\MaterialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaterial extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = MaterialResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
