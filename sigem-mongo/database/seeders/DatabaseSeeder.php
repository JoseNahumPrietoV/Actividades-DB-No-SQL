<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(ServicioSocialUserSeeder::class);
        $this->call(DepartamentoTableSeeder::class);
        $this->call(AreaTableSeeder::class);
        $this->call(TipoMaterialTableSeeder::class);
        $this->call(MarcaMaterialTableSeeder::class);
        $this->call(UnidadMedidaTableSeeder::class);
        $this->call(MaterialTableSeeder::class);
        $this->call(ProveedoresTableSeeder::class);
        $this->call(ReceptorTableSeeder::class);
        $this->call(InventarioTableSeeder::class);
        $this->call(SolicitudTableSeeder::class);
        $this->call(DetalleSolicitudTableSeeder::class);
        $this->call(MantenimientoTableSeeder::class);
        $this->call(HistorialEstadosTableSeeder::class);
    }
}
