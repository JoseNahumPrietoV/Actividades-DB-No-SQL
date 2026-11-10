<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ServicioSocialUserSeeder extends Seeder
{
    public function run(): void
    {
        $servicio = User::updateOrCreate(
            ['email' => 'servicio@tecnm.edu.mx'],
            [
                'name' => 'Alumno Servicio Social',
                'password' => bcrypt('servicio123'),
                'tipo_usuario' => 'Servicio',
                'num_control' => '20240001',
                'carrera' => 'Ingeniería en Sistemas Computacionales',
                'activo' => true,
            ]
        );

        $servicio->assignRole('Servicio Social');
    }
}
