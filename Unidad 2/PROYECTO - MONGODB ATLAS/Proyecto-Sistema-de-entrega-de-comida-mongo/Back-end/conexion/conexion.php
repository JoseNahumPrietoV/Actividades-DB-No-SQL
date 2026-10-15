<?php 
// Back-end/conexion/conexion.php
// Conexión centralizada a MongoDB Atlas (Snackver - Unidad 2 NoSQL)

require_once __DIR__ . '/conexion_mongo.php';

try {
    $mongoDb = MongoConexion::getConexion();
    $db = $mongoDb;
    $conexion = $mongoDb;
} catch (Exception $e) {
    die("Error de conexión con el servidor: " . $e->getMessage());
}
?>