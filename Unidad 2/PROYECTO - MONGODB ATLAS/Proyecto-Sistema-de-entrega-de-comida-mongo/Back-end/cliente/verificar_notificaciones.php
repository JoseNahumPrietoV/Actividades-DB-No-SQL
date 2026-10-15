<?php
// Back-end/cliente/verificar_notificaciones.php - Pure MongoDB Atlas
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

while (ob_get_level() > 0) {
    @ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_cliente'])) {
    echo json_encode([]);
    exit;
}

$id_cliente = $_SESSION['id_cliente'];
session_write_close();

require_once __DIR__ . '/../conexion/conexion_mongo.php';
require_once __DIR__ . '/../servicios/NotificacionService.php';

use Snackver\Servicios\NotificacionService;

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos = $mongoDb->selectCollection('pedidos');

    $filtroCliente = [];
    if (is_string($id_cliente) && preg_match('/^[a-f\d]{24}$/i', $id_cliente)) {
        $filtroCliente['$or'] = [
            ['id_cliente' => new MongoDB\BSON\ObjectId($id_cliente)],
            ['id_cliente' => $id_cliente]
        ];
    } else {
        $filtroCliente['$or'] = [
            ['id_cliente' => (int)$id_cliente],
            ['id_cliente' => (string)$id_cliente]
        ];
    }

    $filtro = [
        '$and' => [
            $filtroCliente,
            [
                '$or' => [
                    ['notificado_cliente' => 0],
                    ['notificado_cliente' => false],
                    ['notificado_cliente' => ['$exists' => false]]
                ]
            ],
            [
                'estadopedido' => [
                    '$in' => ['En preparación', 'Listo', 'Entregado', 'Rechazado', 'Cancelado']
                ]
            ]
        ]
    ];

    $cursor = $colPedidos->find($filtro, [
        'projection' => [
            'id_pedido_num' => 1,
            'estadopedido'  => 1
        ]
    ]);
    $ids_para_actualizar = [];

    foreach ($cursor as $row) {
        $idPedidoStr = (string)($row['id_pedido_num'] ?? $row['_id']);
        $estado = $row['estadopedido'] ?? '';
        $mensaje = "";

        switch ($estado) {
            case 'En preparación':
                $mensaje = "👨‍🍳 ¡Tu pedido se está preparando!";
                break;
            case 'Listo':
                $mensaje = "✅ ¡Tu pedido está LISTO! Puedes pasar por él.";
                break;
            case 'Entregado':
                $mensaje = "🎉 ¡Pedido entregado! Gracias por tu compra.";
                break;
            case 'Rechazado':
                $mensaje = "❌ Lo sentimos, el pedido fue rechazado.";
                break;
            case 'Cancelado':
                $mensaje = "⚠️ El pedido ha sido cancelado.";
                break;
        }

        if (!empty($mensaje)) {
            NotificacionService::crearNotificacion(
                $id_cliente,
                'cliente',
                $mensaje,
                $idPedidoStr,
                'MisPedidos.php'
            );
            $ids_para_actualizar[] = $row['_id'];
        }
    }

    if (!empty($ids_para_actualizar)) {
        $colPedidos->updateMany(
            ['_id' => ['$in' => $ids_para_actualizar]],
            ['$set' => ['notificado_cliente' => 1]]
        );
    }

    $res = NotificacionService::obtenerNotificaciones($id_cliente, 20, 'cliente');
    echo json_encode($res);
    exit;

} catch (Exception $e) {
    error_log("Error en verificar_notificaciones.php: " . $e->getMessage());
    echo json_encode(['notificaciones' => [], 'total_no_leidas' => 0]);
    exit;
}