<?php

namespace App\Observers;

use App\Models\Solicitud;
use Illuminate\Support\Facades\DB;

class SolicitudObserver
{
    public function updated(Solicitud $solicitud): void
    {
        if ($solicitud->isDirty('estado')) {
            $estadoAnterior = $solicitud->getOriginal('estado');
            $estadoNuevo = $solicitud->estado;

            if ($solicitud->tipo_movimiento === 'Asignacion Permanente') {
                if ($estadoAnterior !== 'Autorizado' && $estadoNuevo === 'Autorizado') {
                    // Decrement stock
                    DB::transaction(function () use ($solicitud) {
                        foreach ($solicitud->detalles as $detalle) {
                            if ($detalle->material) {
                                $detalle->material->decrement('stock_actual', $detalle->cantidad);
                            }
                        }
                    });
                } elseif ($estadoAnterior === 'Autorizado' && $estadoNuevo === 'Rechazado') {
                    // Restore stock
                    DB::transaction(function () use ($solicitud) {
                        foreach ($solicitud->detalles as $detalle) {
                            if ($detalle->material) {
                                $detalle->material->increment('stock_actual', $detalle->cantidad);
                            }
                        }
                    });
                }
            }
        }
    }
}
