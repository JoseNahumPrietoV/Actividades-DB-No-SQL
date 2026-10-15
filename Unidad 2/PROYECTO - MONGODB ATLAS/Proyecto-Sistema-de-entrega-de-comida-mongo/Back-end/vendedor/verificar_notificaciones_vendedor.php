<?php
// Back-end/vendedor/verificar_notificaciones_vendedor.php
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

while (ob_get_level() > 0) {
    @ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_vendedor'])) {
    echo json_encode(['notificaciones' => [], 'total_no_leidas' => 0]);
    exit;
}

$id_vendedor = $_SESSION['id_vendedor'];
session_write_close();

require_once dirname(__DIR__) . '/conexion/conexion_mongo.php';
require_once dirname(__DIR__) . '/servicios/NotificacionService.php';

use Snackver\Servicios\NotificacionService;

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos = $mongoDb->selectCollection('pedidos');

    $filtroVendedor = [];
    if (is_string($id_vendedor) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => new MongoDB\BSON\ObjectId($id_vendedor)],
            ['id_vendedor' => (string)$id_vendedor],
            ['id_vendedor' => (int)$id_vendedor]
        ]];
    } else {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => (int)$id_vendedor],
            ['id_vendedor' => (string)$id_vendedor]
        ]];
    }

    $filtro = [
        '$and' => [
            $filtroVendedor,
            ['notificado_vendedor' => 0],
            ['estadopedido' => ['$in' => ['Pendiente', 'Cancelado', 'En preparación']]]
        ]
    ];

    $cursor = $colPedidos->find($filtro, [
        'projection' => [
            'id_pedido_num' => 1,
            'estadopedido'  => 1,
            'total'         => 1
        ]
    ]);
    $ids_para_actualizar = [];

    foreach ($cursor as $row) {
        $idPedido = (string)($row['id_pedido_num'] ?? $row['_id']);
        $estado = $row['estadopedido'] ?? 'Pendiente';
        $total  = number_format((float)($row['total'] ?? 0), 2);
        $mensaje = "";

        switch ($estado) {
            case 'Pendiente':
                $mensaje = "🔔 ¡Nuevo pedido recibido! - $$total";
                break;
            case 'Cancelado':
                $mensaje = "⚠️ El cliente ha cancelado su pedido.";
                break;
            case 'En preparación':
                $mensaje = "💰 ¡Pago confirmado! El pedido se encuentra en preparación.";
                break;
        }

        if (!empty($mensaje)) {
            NotificacionService::crearNotificacion(
                $id_vendedor,
                'vendedor',
                $mensaje,
                $idPedido,
                '../vendedor/Gestion_platillos.php'
            );
            $ids_para_actualizar[] = $row['_id'];
        }
    }

    // Marcar como notificado en pedidos
    if (!empty($ids_para_actualizar)) {
        $colPedidos->updateMany(
            ['_id' => ['$in' => $ids_para_actualizar]],
            ['$set' => ['notificado_vendedor' => 1]]
        );
    }

    $res = NotificacionService::obtenerNotificaciones($id_vendedor, 20, 'vendedor');
    echo json_encode($res);
    exit;

} catch (Exception $e) {
    error_log("Error en verificar_notificaciones_vendedor.php: " . $e->getMessage());
    echo json_encode(['notificaciones' => [], 'total_no_leidas' => 0, 'error' => $e->getMessage()]);
    exit;
}