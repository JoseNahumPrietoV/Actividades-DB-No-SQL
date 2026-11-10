<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MarcaMaterialTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('marca_material')->delete();

        \DB::table('marca_material')->insert([
            0 => [
                'id_marca' => 1,
                'nombre' => 'HP',
                'created_at' => '2026-08-10 16:13:49',
                'updated_at' => '2026-08-10 16:13:49',
            ],
            1 => [
                'id_marca' => 2,
                'nombre' => 'DELL',
                'created_at' => '2026-08-10 16:14:12',
                'updated_at' => '2026-08-10 16:14:12',
            ],
            2 => [
                'id_marca' => 3,
                'nombre' => 'Truper',
                'created_at' => '2026-08-10 16:14:34',
                'updated_at' => '2026-08-10 16:14:34',
            ],
            3 => [
                'id_marca' => 4,
                'nombre' => 'ASUS',
                'created_at' => '2026-08-10 16:48:21',
                'updated_at' => '2026-08-10 16:48:21',
            ],
            4 => [
                'id_marca' => 5,
                'nombre' => 'Samsung',
                'created_at' => '2026-08-10 16:48:40',
                'updated_at' => '2026-08-10 16:48:40',
            ],
            5 => [
                'id_marca' => 6,
                'nombre' => 'Cisco',
                'created_at' => '2026-08-10 16:49:08',
                'updated_at' => '2026-08-10 16:49:08',
            ],
            6 => [
                'id_marca' => 7,
                'nombre' => 'Steren',
                'created_at' => '2026-08-10 16:49:23',
                'updated_at' => '2026-08-10 16:49:23',
            ],
            7 => [
                'id_marca' => 8,
                'nombre' => 'Epson',
                'created_at' => '2026-08-10 16:50:05',
                'updated_at' => '2026-08-10 16:50:05',
            ],
            8 => [
                'id_marca' => 9,
                'nombre' => 'Lenovo',
                'created_at' => '2026-08-10 16:51:04',
                'updated_at' => '2026-08-10 16:51:04',
            ],
        ]);

    }
}
