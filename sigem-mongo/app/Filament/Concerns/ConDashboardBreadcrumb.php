<?php

namespace App\Filament\Concerns;

trait ConDashboardBreadcrumb
{
    public function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();

        $dashboardUrl = request()->is('servicio-social*')
            ? url('/servicio-social')
            : url('/admin');

        return array_merge(
            [$dashboardUrl => 'Dashboard'],
            $breadcrumbs
        );
    }
}
