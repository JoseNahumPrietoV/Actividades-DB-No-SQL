<?php

namespace App\Observers;

use App\Models\Inventario;
use App\Models\Material;

class InventarioObserver
{
    public function created(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->increment('stock_actual');
        }
    }

    public function updating(Inventario $inventario): void
    {
        if ($inventario->isDirty('estado') && $inventario->estado === 'Baja' && empty($inventario->fecha_baja)) {
            $inventario->fecha_baja = now();
        }
    }

    public function updated(Inventario $inventario): void
    {
        if ($inventario->wasChanged('id_producto')) {
            $oldMaterial = Material::find($inventario->getOriginal('id_producto'));
            if ($oldMaterial) {
                $oldMaterial->decrement('stock_actual');
            }
            if ($inventario->material) {
                $inventario->material->increment('stock_actual');
            }
        }
    }

    public function deleted(Inventario $inventario): void
    {
        if ($inventario->id_producto && $inventario->material) {
            $inventario->material->decrement('stock_actual');
        }
    }
}
