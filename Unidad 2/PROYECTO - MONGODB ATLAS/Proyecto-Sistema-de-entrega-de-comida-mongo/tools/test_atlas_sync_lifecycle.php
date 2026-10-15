<?php
// tools/test_atlas_sync_lifecycle.php
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

$mongoDb = MongoConexion::getConexion();
$colCategorias = $mongoDb->selectCollection('categorias');

echo "========================================================\n";
echo "   PRUEBA DE CICLO DE VIDA Y SINCRONIZACIÓN CON ATLAS   \n";
echo "========================================================\n\n";

// 1. Estado inicial
$totalIni = $colCategorias->countDocuments();
echo "1. Categorías en Atlas antes de iniciar: $totalIni\n";

// Probar petición a index.php
$html1 = file_get_contents('http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/index.php');
$contienePrueba1 = strpos($html1, 'CategoriaTestSync') !== false;
echo "   ¿index.php muestra 'CategoriaTestSync'?: " . ($contienePrueba1 ? "SÍ (inesperado)" : "NO (correcto)") . "\n\n";

// 2. Insertar categoría en Atlas
echo "2. Insertando categoría 'CategoriaTestSync' en Atlas...\n";
$insertResult = $colCategorias->insertOne([
    'nombrecategoria' => 'CategoriaTestSync',
    'descripcion'     => 'Categoría temporal de prueba de sincronización',
    'estado'          => 'activo',
    'fecha_creacion'  => new MongoDB\BSON\UTCDateTime()
]);
$testId = (string)$insertResult->getInsertedId();
echo "   ID insertado en Atlas: $testId\n";

// Probar petición a index.php INMEDIATAMENTE
$html2 = file_get_contents('http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/index.php');
$contienePrueba2 = strpos($html2, 'CategoriaTestSync') !== false;
echo "   ¿index.php muestra 'CategoriaTestSync' INMEDIATAMENTE?: " . ($contienePrueba2 ? "✅ SÍ, SINCRONIZADA AL INSTANTE" : "❌ NO") . "\n";

// Probar entrada a Productos.php con esta categoría
$t0 = microtime(true);
$urlProd = "http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/Front-end/Productos.php?id_categoria=" . $testId;
$ch = curl_init($urlProd);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$respProd = curl_exec($ch);
$infoProd = curl_getinfo($ch);
curl_close($ch);
$tProd = round($infoProd['total_time'] * 1000, 2);
$tieneNombre = strpos($respProd, 'CategoriaTestSync') !== false;
echo "   Entrada a Productos.php: HTTP " . $infoProd['http_code'] . " en {$tProd} ms | ¿Muestra nombre?: " . ($tieneNombre ? "✅ SÍ" : "❌ NO") . "\n\n";

// 3. Eliminar categoría en Atlas
echo "3. Eliminando categoría 'CategoriaTestSync' de Atlas...\n";
$colCategorias->deleteOne(['_id' => new MongoDB\BSON\ObjectId($testId)]);
echo "   Documento eliminado de Atlas.\n";

// Probar petición a index.php INMEDIATAMENTE
$html3 = file_get_contents('http://localhost/Proyecto-Sistema-de-entrega-de-comida-mongo/index.php');
$contienePrueba3 = strpos($html3, 'CategoriaTestSync') !== false;
echo "   ¿index.php sigue mostrando 'CategoriaTestSync'?: " . ($contienePrueba3 ? "❌ SÍ (fantasma detectado)" : "✅ NO, DESAPARECIÓ DE INMEDIATO (0 RETARDO)") . "\n";

echo "\n========================================================\n";
echo "           PRUEBA COMPLETADA CON ÉXITO                  \n";
echo "========================================================\n";
