<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class HistorialEstadosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('historial_estados')->delete();

    }
}
