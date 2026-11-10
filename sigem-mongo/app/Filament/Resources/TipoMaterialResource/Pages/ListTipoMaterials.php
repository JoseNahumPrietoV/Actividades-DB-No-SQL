<?php

namespace App\Filament\Resources\TipoMaterialResource\Pages;

use App\Filament\Concerns\ConDashboardBreadcrumb;
use App\Filament\Resources\TipoMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTipoMaterials extends ListRecords
{
    use ConDashboardBreadcrumb;

    protected static string $resource = TipoMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
