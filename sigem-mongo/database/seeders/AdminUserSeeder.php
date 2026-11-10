<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@tecnm.edu.mx'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('admin123'),
                'tipo_usuario' => 'Administrador',
                'activo' => true,
            ]
        );

        $admin->assignRole('Administrador');
    }
}
