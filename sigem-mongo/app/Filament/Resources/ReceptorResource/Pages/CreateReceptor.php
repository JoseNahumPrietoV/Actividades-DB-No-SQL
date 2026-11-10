<?php

namespace App\Filament\Resources\ReceptorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\ReceptorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceptor extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = ReceptorResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
