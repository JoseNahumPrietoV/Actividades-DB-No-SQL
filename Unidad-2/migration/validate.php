<?php
/**
 * Script de validación cruzada MariaDB ↔ MongoDB
 * Compara conteos y genera reporte para el informe
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

// Cargar .env
$envFile = __DIR__ . '/../.env';
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  VALIDACIÓN CRUZADA: MariaDB ↔ MongoDB               ║\n";
echo "║  Proyecto: TechnoGames                               ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

// Conexiones
$mysql = new mysqli('localhost', 'tienda_user', 'password123', 'tienda_videojuegos');
$mongo = new Client(getenv('MONGODB_URI'));
$db = $mongo->selectDatabase('technogames');

// Mapeo: tabla MySQL → colección MongoDB
$checks = [
    ['tabla' => 'usuarios',   'coleccion' => 'usuarios'],
    ['tabla' => 'juegos',     'coleccion' => 'juegos'],
    ['tabla' => 'reseñas',    'coleccion' => 'resenas'],
];

echo str_pad("TABLA MariaDB", 18) . " | " . 
     str_pad("DOCS MongoDB", 15) . " | " . 
     "ESTADO\n";
echo str_repeat("─", 65) . "\n";

$allOk = true;
foreach ($checks as $check) {
    $r = $mysql->query("SELECT COUNT(*) as c FROM {$check['tabla']}");
    $mysqlCount = $r->fetch_assoc()['c'];
    
    $mongoCount = $db->selectCollection($check['coleccion'])->countDocuments();
    
    $status = ($mysqlCount == $mongoCount) ? "✅ OK" : "❌ DIFERENTE";
    if ($mysqlCount != $mongoCount) $allOk = false;
    
    printf("%-18s | %-15s | %s (MySQL: %d)\n", 
        $check['tabla'], 
        $mongoCount . " docs", 
        $status, 
        $mysqlCount
    );
}

echo "\n";

if ($allOk) {
    echo "🎉 ¡MIGRACIÓN VALIDADA EXITOSAMENTE!\n\n";
} else {
    echo "⚠️  Hay discrepancias. Revisar logs de migración.\n\n";
}

// Detalles adicionales
echo "📊 Detalles adicionales:\n\n";

echo "   👥 Usuarios por rol en MongoDB:\n";
$rolAgg = $db->selectCollection('usuarios')->aggregate([
    ['$group' => ['_id' => '$rol', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();
foreach ($rolAgg as $r) {
    printf("      • %-10s %d\n", $r['_id'] . ':', $r['count']);
}

echo "\n   🎮 Juegos por categoría en MongoDB:\n";
$catAgg = $db->selectCollection('juegos')->aggregate([
    ['$group' => ['_id' => '$categoria', 'count' => ['$sum' => 1]]],
    ['$sort' => ['count' => -1]]
])->toArray();
foreach ($catAgg as $c) {
    printf("      • %-15s %d\n", $c['_id'] . ':', $c['count']);
}

echo "\n   💰 Precio promedio de juegos:\n";
$precioAgg = $db->selectCollection('juegos')->aggregate([
    ['$group' => [
        '_id' => null,
        'promedio' => ['$avg' => '$precio_final'],
        'min' => ['$min' => '$precio_final'],
        'max' => ['$max' => '$precio_final']
    ]]
])->toArray()[0];
printf("      • Promedio: \$%.2f\n", $precioAgg['promedio']);
printf("      • Mínimo: \$%.2f\n", $precioAgg['min']);
printf("      • Máximo: \$%.2f\n", $precioAgg['max']);

echo "\n   🏷️  Distribución de descuentos:\n";
$descAgg = $db->selectCollection('juegos')->aggregate([
    ['$group' => [
        '_id' => ['$cond' => [
            ['$gt' => ['$descuento', 0]], 
            'Con descuento', 
            'Sin descuento'
        ]],
        'count' => ['$sum' => 1]
    ]]
])->toArray();
foreach ($descAgg as $d) {
    printf("      • %-15s %d\n", $d['_id'] . ':', $d['count']);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════╗\n";
echo "║  ✅ Validación completada                            ║\n";
echo "╚══════════════════════════════════════════════════════╝\n\n";

$mysql->close();
