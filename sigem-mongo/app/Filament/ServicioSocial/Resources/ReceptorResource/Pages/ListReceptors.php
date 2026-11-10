<?php

namespace App\Filament\ServicioSocial\Resources\ReceptorResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\ServicioSocial\Resources\ReceptorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceptors extends ListRecords
{
    use ConDashboardBreadcrumb;

    protected static string $resource = ReceptorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
