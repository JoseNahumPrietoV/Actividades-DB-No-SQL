<?php

namespace App\Filament\Resources\AreaResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\AreaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = AreaResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
