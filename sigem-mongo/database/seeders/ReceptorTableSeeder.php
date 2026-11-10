<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReceptorTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('receptor')->delete();

        \DB::table('receptor')->insert([
            0 => [
                'id_receptor' => 1,
                'nombre' => 'Alejandro',
                'apellido_paterno' => 'Morales',
                'apellido_materno' => 'Pérez',
                'email' => 'alejandro.perez@itver.edu.mx',
                'telefono' => '2299812345',
                'id_area' => 24,
                'deleted_at' => null,
                'created_at' => '2026-08-10 17:31:49',
                'updated_at' => '2026-08-10 17:31:49',
            ],
        ]);

    }
}
