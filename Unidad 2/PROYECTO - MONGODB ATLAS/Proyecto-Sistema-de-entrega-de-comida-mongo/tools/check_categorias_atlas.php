<?php
// tools/check_categorias_atlas.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$mongoDb = MongoConexion::getConexion();
$col = $mongoDb->selectCollection('categorias');
$all = $col->find()->toArray();

echo "Total categorias en Atlas: " . count($all) . "\n";
foreach ($all as $c) {
    echo " - ID: " . (string)$c['_id'] . " | Nombre: " . ($c['nombrecategoria'] ?? 'Sin nombre') . " | Estado: " . ($c['estado'] ?? 'Sin estado') . "\n";
}
