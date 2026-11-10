<?php

namespace App\Filament\Resources\DepartamentoResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\DepartamentoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDepartamento extends CreateRecord
{
    use ConDashboardBreadcrumb;

    protected static string $resource = DepartamentoResource::class;

    protected function getRedirectUrl(): string
    {
        return url('/admin');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Registro creado exitosamente';
    }
}
