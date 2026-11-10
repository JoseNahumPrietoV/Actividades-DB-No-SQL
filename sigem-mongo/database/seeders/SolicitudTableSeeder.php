<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SolicitudTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('solicitud')->delete();

        \DB::table('solicitud')->insert([
            0 => [
                'id_solicitud' => 1,
                'fecha_solicitud' => '2026-08-10 00:00:00',
                'observaciones' => 'Mantenimiento preventivo e incremento de memoria en laboratorio de cómputo.',
                'fecha_autorizacion' => '2026-08-10 18:34:44',
                'autorizado_por' => 1,
                'estado' => 'Autorizado',
                'fecha_devolucion_estimada' => '2026-08-10 00:00:00',
                'fecha_devolucion_real' => '2026-08-10 00:00:00',
                'id_usuario' => 2,
                'id_receptor' => 1,
                'tipo_movimiento' => 'Asignacion Temporal',
                'created_at' => '2026-08-10 18:28:18',
                'updated_at' => '2026-08-10 18:34:44',
            ],
            1 => [
                'id_solicitud' => 2,
                'fecha_solicitud' => '2026-08-10 00:00:00',
                'observaciones' => 'Mantenimiento preventivo de gabinetes y periféricos en el centro de cómputo.',
                'fecha_autorizacion' => '2026-08-10 18:43:07',
                'autorizado_por' => 1,
                'estado' => 'Autorizado',
                'fecha_devolucion_estimada' => '2026-08-10 00:00:00',
                'fecha_devolucion_real' => '2026-08-10 00:00:00',
                'id_usuario' => 2,
                'id_receptor' => 1,
                'tipo_movimiento' => 'Asignacion Temporal',
                'created_at' => '2026-08-10 18:41:50',
                'updated_at' => '2026-08-10 18:43:07',
            ],
        ]);

    }
}
