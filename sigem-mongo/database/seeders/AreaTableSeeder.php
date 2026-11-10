<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AreaTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('area')->delete();

        \DB::table('area')->insert([
            0 => [
                'id_area' => 1,
                'nombre' => 'Comité de Planeación ',
                'id_departamento' => 12,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:38:36',
                'updated_at' => '2026-08-10 16:09:54',
            ],
            1 => [
                'id_area' => 2,
                'nombre' => 'Comité de Gestión Tecnológica y Vinculación ',
                'id_departamento' => 12,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:42:25',
                'updated_at' => '2026-08-10 16:09:40',
            ],
            2 => [
                'id_area' => 3,
                'nombre' => 'Representante de la Dirección / Gestión de la Calidad (SGC) ',
                'id_departamento' => 12,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:42:58',
                'updated_at' => '2026-08-10 16:09:23',
            ],
            3 => [
                'id_area' => 4,
                'nombre' => 'Coordinación / Comité de Equidad de Género',
                'id_departamento' => 12,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:43:21',
                'updated_at' => '2026-08-10 16:09:01',
            ],
            4 => [
                'id_area' => 5,
                'nombre' => 'Coordinación de Carrera de Ing. en Sistemas Computacionales ',
                'id_departamento' => 2,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:46:13',
                'updated_at' => '2026-08-10 15:46:13',
            ],
            5 => [
                'id_area' => 6,
                'nombre' => 'Coordinación de Carrera de Ing. Mecánica ',
                'id_departamento' => 2,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:46:41',
                'updated_at' => '2026-08-10 15:46:41',
            ],
            6 => [
                'id_area' => 7,
                'nombre' => 'Coordinación de Maestría en Ingeniería Bioquímica ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:48:09',
                'updated_at' => '2026-08-10 15:48:09',
            ],
            7 => [
                'id_area' => 8,
                'nombre' => 'Coordinación de Maestría en Eficiencia Energética y Energías Renovables ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:48:27',
                'updated_at' => '2026-08-10 15:48:27',
            ],
            8 => [
                'id_area' => 9,
                'nombre' => 'Coordinación de Maestría en Administración ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:48:51',
                'updated_at' => '2026-08-10 15:48:51',
            ],
            9 => [
                'id_area' => 10,
                'nombre' => 'Coordinación de Maestría en Inteligencia Artificial ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:49:19',
                'updated_at' => '2026-08-10 15:49:19',
            ],
            10 => [
                'id_area' => 11,
                'nombre' => 'Coordinación del Doctorado en Ciencias en Alimentos (UNIDA) ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:50:06',
                'updated_at' => '2026-08-10 15:50:06',
            ],
            11 => [
                'id_area' => 12,
                'nombre' => 'Coordinación del Doctorado en Ciencias Ambientales ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:50:30',
                'updated_at' => '2026-08-10 15:50:30',
            ],
            12 => [
                'id_area' => 13,
                'nombre' => 'Coordinación del Doctorado en Ciencias de la Ingeniería ',
                'id_departamento' => 3,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:50:50',
                'updated_at' => '2026-08-10 15:50:50',
            ],
            13 => [
                'id_area' => 14,
                'nombre' => 'Proyecto Docente / Proyectos de Investigación y Vinculación ',
                'id_departamento' => 4,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:51:44',
                'updated_at' => '2026-08-10 15:51:44',
            ],
            14 => [
                'id_area' => 15,
                'nombre' => 'Aulas y Laboratorios de Cómputo Académico',
                'id_departamento' => 4,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:52:05',
                'updated_at' => '2026-08-10 15:52:05',
            ],
            15 => [
                'id_area' => 16,
                'nombre' => 'Área de Matemáticas, Física y Química Básica',
                'id_departamento' => 5,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:53:02',
                'updated_at' => '2026-08-10 15:53:02',
            ],
            16 => [
                'id_area' => 17,
                'nombre' => 'Aula CAD / DiseñoAsistido por Computadora',
                'id_departamento' => 5,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:53:32',
                'updated_at' => '2026-08-10 15:53:32',
            ],
            17 => [
                'id_area' => 18,
                'nombre' => 'Área Docente de Eléctrica y Electrónica',
                'id_departamento' => 6,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:55:21',
                'updated_at' => '2026-08-10 15:55:21',
            ],
            18 => [
                'id_area' => 19,
                'nombre' => 'Proyectos de Investigación y Vinculación en Electrónica ',
                'id_departamento' => 6,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:55:43',
                'updated_at' => '2026-08-10 15:55:43',
            ],
            19 => [
                'id_area' => 20,
                'nombre' => 'Área Docente de Química y Bioquímica ',
                'id_departamento' => 7,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:57:00',
                'updated_at' => '2026-08-10 15:57:00',
            ],
            20 => [
                'id_area' => 21,
                'nombre' => 'Laboratorios Especializados y UNIDA',
                'id_departamento' => 7,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:57:22',
                'updated_at' => '2026-08-10 15:57:22',
            ],
            21 => [
                'id_area' => 22,
                'nombre' => 'Área Docente y Talleres Pesados',
                'id_departamento' => 8,
                'deleted_at' => null,
                'created_at' => '2026-08-10 15:58:19',
                'updated_at' => '2026-08-10 15:58:19',
            ],
            22 => [
                'id_area' => 23,
                'nombre' => 'Proyectos de Investigación ',
                'id_departamento' => 8,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:00:03',
                'updated_at' => '2026-08-10 16:00:03',
            ],
            23 => [
                'id_area' => 24,
                'nombre' => 'Proyectos de Vinculación e Investigación Industrial ',
                'id_departamento' => 9,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:00:45',
                'updated_at' => '2026-08-10 16:00:45',
            ],
            24 => [
                'id_area' => 25,
                'nombre' => 'Área Docente y Laboratorios de Gestión/Administración',
                'id_departamento' => 10,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:01:21',
                'updated_at' => '2026-08-10 16:01:21',
            ],
            25 => [
                'id_area' => 26,
                'nombre' => 'Coordinación de Actualización y Formación Docente',
                'id_departamento' => 11,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:02:13',
                'updated_at' => '2026-08-10 16:02:13',
            ],
            26 => [
                'id_area' => 27,
                'nombre' => 'Coordinación de Orientación Educativa y Tutorías',
                'id_departamento' => 11,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:02:32',
                'updated_at' => '2026-08-10 16:02:32',
            ],
            27 => [
                'id_area' => 28,
                'nombre' => 'Coordinación de Investigación Educativa ',
                'id_departamento' => 11,
                'deleted_at' => null,
                'created_at' => '2026-08-10 16:02:51',
                'updated_at' => '2026-08-10 16:02:51',
            ],
        ]);

    }
}
