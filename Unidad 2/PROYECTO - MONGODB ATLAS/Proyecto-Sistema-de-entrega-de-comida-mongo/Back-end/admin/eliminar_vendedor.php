<?php
// Back-end/admin/eliminar_vendedor.php - Pure MongoDB Atlas
session_start();

// 1. Verificación de permisos de Administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

require_once __DIR__ . '/../conexion/conexion_mongo.php';
require_once __DIR__ . '/../servicios/HistorialService.php';

use Snackver\Servicios\HistorialService;
use MongoDB\BSON\ObjectId;

$id = isset($_POST['id']) && is_string($_POST['id']) ? trim($_POST['id']) : '';
if (empty($id)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (is_array($input) && isset($input['id']) && is_string($input['id'])) {
            $id = trim($input['id']);
        }
    }
}

if (empty($id)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'ID de vendedor no proporcionado.']);
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');
    $colProductos = $mongoDb->selectCollection('productos');

    // Construir filtro flexible para ObjectId o identificadores numéricos/alfanuméricos
    $filtro = [];
    if (preg_match('/^[a-f\d]{24}$/i', $id)) {
        $filtro = ['_id' => new ObjectId($id), 'rol' => 'vendedor'];
    } else {
        $filtro = [
            '$or' => [
                ['_id' => (string)$id]
            ],
            'rol' => 'vendedor'
        ];
        if (is_numeric($id)) {
            $filtro['$or'][] = ['_id' => (int)$id];
        }
    }

    $vendedor = $colUsuarios->findOne($filtro);
    if (!$vendedor) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Vendedor no encontrado.']);
        exit();
    }

    $idReal = $vendedor['_id'];
    $emailVendedor = $vendedor['email'] ?? '';
    $nombreCompleto = trim(($vendedor['nombre'] ?? '') . ' ' . ($vendedor['apellido'] ?? ''));
    $tienda = $vendedor['tienda'] ?? [];
    $nombreNegocio = $tienda['nombre_negocio'] ?? ($vendedor['nombre_negocio'] ?? 'Sin Negocio');

    // 2. Eliminar de la colección usuarios
    $colUsuarios->deleteOne(['_id' => $idReal]);

    // 3. Eliminar productos pertenecientes al vendedor
    $filtroProductos = [
        '$or' => [
            ['id_vendedor' => $idReal],
            ['id_vendedor' => (string)$idReal]
        ]
    ];
    if (is_numeric((string)$idReal)) {
        $filtroProductos['$or'][] = ['id_vendedor' => (int)$idReal];
    }
    $colProductos->deleteMany($filtroProductos);

    // 4. Registrar evento de auditoría en HistorialService
    HistorialService::registrar(
        $idReal,
        $emailVendedor,
        'vendedor',
        'Usuario eliminado',
        [
            'motivo'          => 'Eliminación por administrador',
            'nombre_completo' => $nombreCompleto,
            'negocio'         => $nombreNegocio,
            'telefono'        => $vendedor['telefono'] ?? ''
        ]
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status'  => 'success',
        'message' => 'Vendedor eliminado permanentemente.'
    ]);
    exit();

} catch (Exception $e) {
    error_log("Error en eliminar_vendedor.php: " . $e->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Error en el servidor al eliminar el vendedor.']);
    exit();
}
