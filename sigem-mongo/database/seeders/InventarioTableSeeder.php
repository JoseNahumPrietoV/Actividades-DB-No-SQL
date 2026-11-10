<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InventarioTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('inventario')->delete();

        \DB::table('inventario')->insert([
            0 => [
                'id_inventario' => 1,
                'num_serie' => '9876543234567',
                'id_producto' => 2,
                'id_usuario' => 1,
                'id_proveedor' => 1,
                'estado' => 'Disponible',
                'estado_registro' => 'Aprobado',
                'tipo_propiedad' => 'Rentado',
                'ubicacion_fisica' => null,
                'fecha_registro' => '2026-08-10 00:00:00',
                'fecha_factura' => '2026-08-10 00:00:00',
                'num_factura' => '69885075',
                'fecha_baja' => null,
                'fecha_inicio_renta' => '2026-08-10 00:00:00',
                'fecha_fin_renta' => '2026-08-29 00:00:00',
                'observaciones_renta' => null,
                'observaciones_generales' => null,
                'garantia_fecha_fin' => '2026-08-30 00:00:00',
                'garantia_estado' => 'vigente',
                'deleted_at' => null,
                'created_at' => '2026-08-10 18:20:42',
                'updated_at' => '2026-08-10 18:20:42',
                'aprobado' => 0,
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ],
            1 => [
                'id_inventario' => 2,
                'num_serie' => 'DELL-OPT7010-001',
                'id_producto' => 3,
                'id_usuario' => 1,
                'id_proveedor' => null,
                'estado' => 'En Mantenimiento',
                'estado_registro' => 'Aprobado',
                'tipo_propiedad' => 'Propio',
                'ubicacion_fisica' => 'Computo',
                'fecha_registro' => '2026-08-14 00:00:00',
                'fecha_factura' => null,
                'num_factura' => null,
                'fecha_baja' => null,
                'fecha_inicio_renta' => null,
                'fecha_fin_renta' => null,
                'observaciones_renta' => null,
                'observaciones_generales' => null,
                'garantia_fecha_fin' => null,
                'garantia_estado' => 'sin_garantia',
                'deleted_at' => null,
                'created_at' => '2026-08-14 17:19:30',
                'updated_at' => '2026-08-14 17:21:40',
                'aprobado' => 0,
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ],
            2 => [
                'id_inventario' => 3,
                'num_serie' => 'HP-LJ4103-ADM01',
                'id_producto' => 4,
                'id_usuario' => 1,
                'id_proveedor' => null,
                'estado' => 'Disponible',
                'estado_registro' => 'Aprobado',
                'tipo_propiedad' => 'Propio',
                'ubicacion_fisica' => 'Computo',
                'fecha_registro' => '2026-08-14 00:00:00',
                'fecha_factura' => null,
                'num_factura' => null,
                'fecha_baja' => null,
                'fecha_inicio_renta' => null,
                'fecha_fin_renta' => null,
                'observaciones_renta' => null,
                'observaciones_generales' => null,
                'garantia_fecha_fin' => null,
                'garantia_estado' => 'sin_garantia',
                'deleted_at' => null,
                'created_at' => '2026-08-14 17:20:20',
                'updated_at' => '2026-08-14 17:20:20',
                'aprobado' => 0,
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ],
        ]);

    }
}
