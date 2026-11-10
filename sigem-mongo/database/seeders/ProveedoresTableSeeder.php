<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProveedoresTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('proveedores')->delete();

        \DB::table('proveedores')->insert([
            0 => [
                'id_proveedor' => 1,
                'nombre_empresa' => 'Cyberpuerta S.A. de C.V.',
                'rfc' => 'CYB120410AB3',
                'contacto_nombre' => 'Carlos Eduardo Mendoza',
                'contacto_telefono' => '2299312045',
                'contacto_email' => 'ventas.veracruz@cyberpuerta.mx',
                'activo' => 1,
                'created_at' => '2026-08-10 17:22:56',
                'updated_at' => '2026-08-10 17:22:56',
            ],
            1 => [
                'id_proveedor' => 2,
                'nombre_empresa' => 'Electrónica Steren de Veracruz S.A. de C.V.',
                'rfc' => 'EST850615A89',
                'contacto_nombre' => 'Mariana Torres Ruiz',
                'contacto_telefono' => '2299324578',
                'contacto_email' => 'atencion.clientes@sterenveracruz.com',
                'activo' => 1,
                'created_at' => '2026-08-10 17:25:24',
                'updated_at' => '2026-08-10 17:25:24',
            ],
            2 => [
                'id_proveedor' => 3,
                'nombre_empresa' => 'Soluciones de Redes y Conectividad Panduit',
                'rfc' => 'SRC150820KL4',
                'contacto_nombre' => 'Roberto Gómez Hernández',
                'contacto_telefono' => '2299801122',
                'contacto_email' => 'rgomez@redesyconectividad.com.mx',
                'activo' => 1,
                'created_at' => '2026-08-10 17:29:34',
                'updated_at' => '2026-08-10 17:29:34',
            ],
        ]);

    }
}
