<?php
// admin/actualizar_estado_vendedor.php - Pure MongoDB Atlas
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';

$id = isset($_POST['id']) ? trim($_POST['id']) : '';
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

if (!empty($id) && ($estado === 'activo' || $estado === 'inactivo')) {
    try {
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');

        $filtro = [];
        if (preg_match('/^[a-f\d]{24}$/i', $id)) {
            $filtro['_id'] = new MongoDB\BSON\ObjectId($id);
        } else {
            $filtro['$or'] = [
                ['_id' => (int)$id],
                ['_id' => (string)$id]
            ];
        }

        $res = $colUsuarios->updateOne($filtro, ['$set' => ['estado' => $estado]]);
        if ($res->getMatchedCount() > 0) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Vendedor no encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
}