<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DetalleSolicitudTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('detalle_solicitud')->delete();

        \DB::table('detalle_solicitud')->insert([
            0 => [
                'id_detalle' => 1,
                'cantidad' => 1,
                'id_solicitud' => 1,
                'id_producto' => 2,
                'id_inventario' => null,
                'created_at' => '2026-08-10 18:28:18',
                'updated_at' => '2026-08-10 18:28:18',
            ],
            1 => [
                'id_detalle' => 2,
                'cantidad' => 1,
                'id_solicitud' => 1,
                'id_producto' => 1,
                'id_inventario' => null,
                'created_at' => '2026-08-10 18:28:18',
                'updated_at' => '2026-08-10 18:28:18',
            ],
            2 => [
                'id_detalle' => 3,
                'cantidad' => 2,
                'id_solicitud' => 2,
                'id_producto' => 2,
                'id_inventario' => null,
                'created_at' => '2026-08-10 18:41:50',
                'updated_at' => '2026-08-10 18:41:50',
            ],
        ]);

    }
}
