<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UnidadMedidaTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('unidad_medida')->delete();

        \DB::table('unidad_medida')->insert([
            0 => [
                'id_unidad' => 41,
                'nombre' => 'Pieza (Pza / Pz)',
                'created_at' => '2026-08-10 17:48:49',
                'updated_at' => '2026-08-10 17:48:49',
            ],
            1 => [
                'id_unidad' => 42,
                'nombre' => 'Metro (m)',
                'created_at' => '2026-08-10 17:49:08',
                'updated_at' => '2026-08-10 17:49:08',
            ],
            2 => [
                'id_unidad' => 43,
                'nombre' => 'Bobina / Rollo',
                'created_at' => '2026-08-10 17:49:28',
                'updated_at' => '2026-08-10 17:49:28',
            ],
            3 => [
                'id_unidad' => 44,
                'nombre' => 'Bote / Lata / Envase',
                'created_at' => '2026-08-10 17:49:42',
                'updated_at' => '2026-08-10 17:49:42',
            ],
            4 => [
                'id_unidad' => 45,
                'nombre' => 'Juego / Kit',
                'created_at' => '2026-08-10 17:49:57',
                'updated_at' => '2026-08-10 17:49:57',
            ],
            5 => [
                'id_unidad' => 46,
                'nombre' => 'Paquete (Pqt)',
                'created_at' => '2026-08-10 17:50:10',
                'updated_at' => '2026-08-10 17:50:10',
            ],
            6 => [
                'id_unidad' => 47,
                'nombre' => 'Caja',
                'created_at' => '2026-08-10 17:50:21',
                'updated_at' => '2026-08-10 17:50:21',
            ],
        ]);

    }
}
