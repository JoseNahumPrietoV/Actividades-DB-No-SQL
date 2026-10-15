<?php
// tools/test_warm_query.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$mongoDb = MongoConexion::getConexion();
// conexion ya caliente
$t0 = microtime(true);
$colCategorias = $mongoDb->selectCollection('categorias');
$cursor = $colCategorias->find(
    ['estado' => 'activo'],
    [
        'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'imagen' => 1],
        'sort'       => ['nombrecategoria' => 1]
    ]
)->toArray();
$t1 = microtime(true);

echo "Tiempo de consulta de categorias con conexion caliente: " . round(($t1 - $t0) * 1000, 2) . " ms\n";
