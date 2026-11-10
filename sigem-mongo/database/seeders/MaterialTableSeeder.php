<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MaterialTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('material')->delete();

        \DB::table('material')->insert([
            0 => [
                'id_producto' => 1,
                'nombre' => 'Memoria RAM DDR4 16GB 3200MHz',
                'descripcion' => 'Módulo de memoria RAM UDIMM DDR4 de 16 GB a 3200 MHz para actualización y mantenimiento de PC de escritorio en laboratorios.',
                'modelo' => 'ValueRAM KVR32N22D8/16',
                'id_unidad' => 41,
                'id_marca' => 2,
                'id_tipodematerial' => 9,
                'requiere_control_individual' => 1,
                'stock_actual' => 10,
                'stock_minimo' => 5,
                'deleted_at' => null,
                'created_at' => '2026-08-10 18:13:29',
                'updated_at' => '2026-08-14 16:58:54',
            ],
            1 => [
                'id_producto' => 2,
                'nombre' => 'Aire Comprimido Limpiador 660ml',
                'descripcion' => 'Bote de aire comprimido con removedor de polvo seco para mantenimiento preventivo de componentes de cómputo y teclados.',
                'modelo' => 'Aerojet Remover 660',
                'id_unidad' => 44,
                'id_marca' => 9,
                'id_tipodematerial' => 10,
                'requiere_control_individual' => 1,
                'stock_actual' => 15,
                'stock_minimo' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 18:16:45',
                'updated_at' => '2026-08-14 16:58:27',
            ],
            2 => [
                'id_producto' => 3,
                'nombre' => 'Computadora de Escritorio Dell OptiPlex 7010 SFF',
                'descripcion' => 'Equipo de escritorio para laboratorio con procesador Intel Core i5, 16GB RAM y 512GB SSD.',
                'modelo' => 'OptiPlex 7010 Small Form Factor',
                'id_unidad' => 41,
                'id_marca' => 2,
                'id_tipodematerial' => 14,
                'requiere_control_individual' => 1,
                'stock_actual' => 4,
                'stock_minimo' => 1,
                'deleted_at' => null,
                'created_at' => '2026-08-14 17:13:53',
                'updated_at' => '2026-08-14 17:19:30',
            ],
            3 => [
                'id_producto' => 4,
                'nombre' => 'Impresora Multifuncional HP LaserJet Pro MFP 4103fdw',
                'descripcion' => 'Impresora láser monocromática multifuncional con conectividad Ethernet, Wi-Fi y escáner dúplex para oficina.',
                'modelo' => 'LaserJet Pro MFP 4103fdw',
                'id_unidad' => 41,
                'id_marca' => 1,
                'id_tipodematerial' => 13,
                'requiere_control_individual' => 1,
                'stock_actual' => 3,
                'stock_minimo' => 1,
                'deleted_at' => null,
                'created_at' => '2026-08-14 17:16:18',
                'updated_at' => '2026-08-14 17:20:20',
            ],
        ]);

    }
}
