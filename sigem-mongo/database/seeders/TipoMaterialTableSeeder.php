<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TipoMaterialTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('tipo_material')->delete();

        \DB::table('tipo_material')->insert([
            0 => [
                'id_tipodematerial' => 8,
                'nombre' => 'Muse',
                'created_at' => '2026-08-10 17:59:27',
                'updated_at' => '2026-08-10 17:59:27',
            ],
            1 => [
                'id_tipodematerial' => 9,
                'nombre' => 'Memoria(Ram)',
                'created_at' => '2026-08-10 18:00:03',
                'updated_at' => '2026-08-10 18:00:03',
            ],
            2 => [
                'id_tipodematerial' => 10,
                'nombre' => 'Aire comprimido',
                'created_at' => '2026-08-10 18:00:25',
                'updated_at' => '2026-08-10 18:00:25',
            ],
            3 => [
                'id_tipodematerial' => 11,
                'nombre' => 'Disco duro',
                'created_at' => '2026-08-10 18:00:36',
                'updated_at' => '2026-08-10 18:00:36',
            ],
            4 => [
                'id_tipodematerial' => 12,
                'nombre' => 'SSD',
                'created_at' => '2026-08-10 18:00:53',
                'updated_at' => '2026-08-10 18:00:53',
            ],
            5 => [
                'id_tipodematerial' => 13,
                'nombre' => 'Tóner',
                'created_at' => '2026-08-10 18:01:10',
                'updated_at' => '2026-08-10 18:01:10',
            ],
            6 => [
                'id_tipodematerial' => 14,
                'nombre' => 'CPU',
                'created_at' => '2026-08-10 18:01:25',
                'updated_at' => '2026-08-10 18:01:25',
            ],
            7 => [
                'id_tipodematerial' => 15,
                'nombre' => 'Monitor ',
                'created_at' => '2026-08-10 18:01:39',
                'updated_at' => '2026-08-10 18:01:39',
            ],
            8 => [
                'id_tipodematerial' => 16,
                'nombre' => 'Antena(USB)',
                'created_at' => '2026-08-10 18:01:56',
                'updated_at' => '2026-08-10 18:01:56',
            ],
            9 => [
                'id_tipodematerial' => 17,
                'nombre' => 'Antena(WIFI)',
                'created_at' => '2026-08-10 18:02:14',
                'updated_at' => '2026-08-10 18:02:14',
            ],
            10 => [
                'id_tipodematerial' => 18,
                'nombre' => 'Antena(RED)',
                'created_at' => '2026-08-10 18:02:32',
                'updated_at' => '2026-08-10 18:02:32',
            ],
            11 => [
                'id_tipodematerial' => 19,
                'nombre' => 'Espuma limpiadores ',
                'created_at' => '2026-08-10 18:02:58',
                'updated_at' => '2026-08-10 18:02:58',
            ],
            12 => [
                'id_tipodematerial' => 20,
                'nombre' => 'Alcohol isopropílico',
                'created_at' => '2026-08-10 18:03:14',
                'updated_at' => '2026-08-10 18:03:14',
            ],
            13 => [
                'id_tipodematerial' => 21,
                'nombre' => 'Adaptador de disco duro',
                'created_at' => '2026-08-10 18:03:29',
                'updated_at' => '2026-08-10 18:03:29',
            ],
            14 => [
                'id_tipodematerial' => 22,
                'nombre' => 'Adaptador de SSD',
                'created_at' => '2026-08-10 18:03:41',
                'updated_at' => '2026-08-10 18:03:41',
            ],
            15 => [
                'id_tipodematerial' => 23,
                'nombre' => 'Teclado',
                'created_at' => '2026-08-10 18:03:57',
                'updated_at' => '2026-08-10 18:03:57',
            ],
            16 => [
                'id_tipodematerial' => 24,
                'nombre' => 'Toalla ',
                'created_at' => '2026-08-10 18:04:07',
                'updated_at' => '2026-08-10 18:04:07',
            ],
            17 => [
                'id_tipodematerial' => 25,
                'nombre' => 'Fuente de poder',
                'created_at' => '2026-08-10 18:04:23',
                'updated_at' => '2026-08-10 18:04:23',
            ],
            18 => [
                'id_tipodematerial' => 26,
                'nombre' => 'Cable(UTP)',
                'created_at' => '2026-08-10 18:04:53',
                'updated_at' => '2026-08-10 18:04:53',
            ],
            19 => [
                'id_tipodematerial' => 27,
                'nombre' => 'Cable(K6)',
                'created_at' => '2026-08-10 18:05:09',
                'updated_at' => '2026-08-10 18:05:09',
            ],
            20 => [
                'id_tipodematerial' => 28,
                'nombre' => 'Cable de video(HDMI)',
                'created_at' => '2026-08-10 18:05:30',
                'updated_at' => '2026-08-10 18:05:30',
            ],
            21 => [
                'id_tipodematerial' => 29,
                'nombre' => 'Batería(UPS)',
                'created_at' => '2026-08-10 18:05:58',
                'updated_at' => '2026-08-10 18:05:58',
            ],
            22 => [
                'id_tipodematerial' => 30,
                'nombre' => 'Limpiador de circuitos',
                'created_at' => '2026-08-10 18:06:24',
                'updated_at' => '2026-08-10 18:06:24',
            ],
            23 => [
                'id_tipodematerial' => 31,
                'nombre' => 'Cable de impresora ',
                'created_at' => '2026-08-10 18:06:38',
                'updated_at' => '2026-08-10 18:06:38',
            ],
            24 => [
                'id_tipodematerial' => 32,
                'nombre' => 'Cable de corriente',
                'created_at' => '2026-08-10 18:06:51',
                'updated_at' => '2026-08-10 18:06:51',
            ],
            25 => [
                'id_tipodematerial' => 33,
                'nombre' => 'Disipadores',
                'created_at' => '2026-08-10 18:07:03',
                'updated_at' => '2026-08-10 18:07:03',
            ],
            26 => [
                'id_tipodematerial' => 34,
                'nombre' => 'Bocinas',
                'created_at' => '2026-08-10 18:07:13',
                'updated_at' => '2026-08-10 18:07:13',
            ],
            27 => [
                'id_tipodematerial' => 35,
                'nombre' => 'Abrazadores ',
                'created_at' => '2026-08-10 18:07:22',
                'updated_at' => '2026-08-10 18:07:22',
            ],
            28 => [
                'id_tipodematerial' => 36,
                'nombre' => 'Desarmador para laptop',
                'created_at' => '2026-08-10 18:07:35',
                'updated_at' => '2026-08-10 18:07:35',
            ],
            29 => [
                'id_tipodematerial' => 37,
                'nombre' => 'Desarmador de cruz',
                'created_at' => '2026-08-10 18:07:59',
                'updated_at' => '2026-08-10 18:07:59',
            ],
            30 => [
                'id_tipodematerial' => 38,
                'nombre' => 'Pinzas de corte',
                'created_at' => '2026-08-10 18:08:27',
                'updated_at' => '2026-08-10 18:08:27',
            ],
            31 => [
                'id_tipodematerial' => 39,
                'nombre' => 'Pinzas de punta',
                'created_at' => '2026-08-10 18:08:41',
                'updated_at' => '2026-08-10 18:08:41',
            ],
            32 => [
                'id_tipodematerial' => 40,
                'nombre' => 'Brocha antiestática',
                'created_at' => '2026-08-10 18:09:11',
                'updated_at' => '2026-08-10 18:09:11',
            ],
            33 => [
                'id_tipodematerial' => 41,
                'nombre' => 'Ponchadores ',
                'created_at' => '2026-08-10 18:09:21',
                'updated_at' => '2026-08-10 18:09:21',
            ],
            34 => [
                'id_tipodematerial' => 42,
                'nombre' => 'Cinchos ',
                'created_at' => '2026-08-10 18:09:29',
                'updated_at' => '2026-08-10 18:09:29',
            ],
            35 => [
                'id_tipodematerial' => 43,
                'nombre' => 'Cargador de laptop',
                'created_at' => '2026-08-10 18:09:42',
                'updated_at' => '2026-08-10 18:09:42',
            ],
            36 => [
                'id_tipodematerial' => 44,
                'nombre' => 'Limpiador para pantallas ',
                'created_at' => '2026-08-10 18:09:57',
                'updated_at' => '2026-08-10 18:09:57',
            ],
            37 => [
                'id_tipodematerial' => 45,
                'nombre' => 'Extensores USB',
                'created_at' => '2026-08-10 18:10:17',
                'updated_at' => '2026-08-10 18:10:17',
            ],
            38 => [
                'id_tipodematerial' => 46,
                'nombre' => 'Elector de DVD externo',
                'created_at' => '2026-08-10 18:10:38',
                'updated_at' => '2026-08-10 18:10:38',
            ],
            39 => [
                'id_tipodematerial' => 47,
                'nombre' => 'POE',
                'created_at' => '2026-08-10 18:10:48',
                'updated_at' => '2026-08-10 18:10:48',
            ],
        ]);

    }
}
