<?php
// tools/test_productos_timing.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$mongoDb = MongoConexion::getConexion();
$cat = $mongoDb->selectCollection("categorias")->findOne(["estado" => "activo"]);
if (!$cat) {
    echo "No hay categorias activas\n";
    exit;
}
$catId = (string)$cat["_id"];
$catName = $cat["nombrecategoria"];
echo "Categoria probada: $catName (ID: $catId)\n";

$url = "http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/Front-end/Productos.php?id_categoria=" . $catId;

// Medir 3 peticiones consecutivas
for ($i = 1; $i <= 3; $i++) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    echo "Peticion $i: HTTP " . $info["http_code"] . " en " . round($info["total_time"] * 1000, 2) . " ms\n";
}
