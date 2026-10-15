<?php
// Front-end/obtener_categorias.php - Pure MongoDB Atlas
require_once __DIR__ . '/../Back-end/conexion/conexion_mongo.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$categorias_db = [];
$cacheCatKey = 'snackver_cache_categorias';
$cacheCatTime = 'snackver_cache_categorias_time';
$ahora = time();

try {
    $mongoDb = MongoConexion::getConexion();
    $colCategorias = $mongoDb->selectCollection('categorias');
    $cursor = $colCategorias->find(
        ['estado' => 'activo'],
        [
            'projection' => ['nombrecategoria' => 1, 'descripcion' => 1, 'imagen' => 1],
            'sort'       => ['nombrecategoria' => 1]
        ]
    );

    foreach ($cursor as $doc) {
        $categorias_db[] = [
            'idcategoria'     => (string)$doc['_id'],
            'nombrecategoria' => $doc['nombrecategoria'] ?? '',
            'descripcion'     => $doc['descripcion'] ?? '',
            'imagen'          => $doc['imagen'] ?? ''
        ];
    }
    $_SESSION[$cacheCatKey] = $categorias_db;
    $_SESSION[$cacheCatTime] = time();
} catch (Exception $e) {
    error_log("Error cargando categorías en obtener_categorias.php: " . $e->getMessage());
    $categorias_db = $_SESSION[$cacheCatKey] ?? [];
}

// Liberar el candado de sesión inmediatamente para peticiones aisladas.
// Si se carga desde index.php, la sesión se libera tras procesar las reseñas para persistir ambos bloques.
if (!defined('SNACKVER_PORTADA') && session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// Obtener la hora actual para el saludo dinámico
$hour = date('G');
if ($hour < 12) {
    $greeting = "¡Buenos días!";
} elseif ($hour < 19) {
    $greeting = "¡Buenas tardes!";
} else {
    $greeting = "¡Buenas noches!";
}