<?php
// Back-end/vendedor/actualizar_estado_pedido.php
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';

header('Content-Type: application/json');

if (!isset($_SESSION['id_vendedor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada']);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$id_pedido = $_POST['idpedido'] ?? '';
$nuevo_estado = $_POST['nuevo_estado'] ?? '';

if (empty($id_pedido) || empty($nuevo_estado)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos.']);
    exit;
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos   = $mongoDb->selectCollection('pedidos');
    $colProductos = $mongoDb->selectCollection('productos');

    $filtroPedido = [];
    if (is_string($id_pedido) && preg_match('/^[a-f\d]{24}$/i', $id_pedido)) {
        $filtroPedido = ['_id' => new MongoDB\BSON\ObjectId($id_pedido)];
    } else {
        $filtroPedido = ['$or' => [
            ['id_pedido_num' => (int)$id_pedido],
            ['_id' => (string)$id_pedido],
            ['_id' => (int)$id_pedido]
        ]];
    }

    $pedido = $colPedidos->findOne($filtroPedido, [
        'projection' => [
            'id_pedido_num' => 1,
            'estadopedido'  => 1,
            'items'         => 1,
            'id_cliente'    => 1
        ]
    ]);
    if (!$pedido) {
        echo json_encode(['status' => 'error', 'message' => 'Pedido no encontrado.']);
        exit;
    }

    $estado_actual = $pedido['estadopedido'] ?? 'Pendiente';

    // Si pasamos a Rechazado o Cancelado desde un estado activo, devolver stock
    if (($nuevo_estado === 'Rechazado' || $nuevo_estado === 'Cancelado') && 
        $estado_actual !== 'Rechazado' && $estado_actual !== 'Cancelado') {
        
        $items = $pedido['items'] ?? [];
        foreach ($items as $item) {
            $idProd = $item['id_producto'] ?? null;
            $cant   = (int)($item['cantidad'] ?? 0);
            if ($idProd && $cant > 0) {
                $filtroProd = [];
                if (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd)) {
                    $filtroProd = ['_id' => new MongoDB\BSON\ObjectId($idProd)];
                } elseif ($idProd instanceof MongoDB\BSON\ObjectId) {
                    $filtroProd = ['_id' => $idProd];
                } else {
                    $filtroProd = ['_id' => $idProd];
                }
                $colProductos->updateOne($filtroProd, ['$inc' => ['stock_disponible' => $cant]]);
            }
        }
    }

    // Actualizar estado del pedido y marcar notificación para el cliente
    $colPedidos->updateOne(
        $filtroPedido,
        ['$set' => [
            'estadopedido'       => $nuevo_estado,
            'notificado_cliente' => 1
        ]]
    );

    // Crear notificación persistente en tiempo real para el cliente
    if (!empty($pedido['id_cliente'])) {
        require_once dirname(__DIR__) . '/servicios/NotificacionService.php';
        $mensajeCli = "Tu pedido ha cambiado de estado: $nuevo_estado.";
        switch ($nuevo_estado) {
            case 'En preparación':
                $mensajeCli = "👨‍🍳 ¡Tu pedido se está preparando!";
                break;
            case 'Listo':
                $mensajeCli = "✅ ¡Tu pedido está LISTO! Puedes pasar por él.";
                break;
            case 'Entregado':
                $mensajeCli = "🎉 ¡Pedido entregado! Gracias por tu compra.";
                break;
            case 'Rechazado':
                $mensajeCli = "❌ Lo sentimos, el pedido fue rechazado por el vendedor.";
                break;
            case 'Cancelado':
                $mensajeCli = "⚠️ El pedido ha sido cancelado.";
                break;
        }
        \Snackver\Servicios\NotificacionService::crearNotificacion(
            $pedido['id_cliente'],
            'cliente',
            $mensajeCli,
            (string)($pedido['id_pedido_num'] ?? $pedido['_id']),
            'MisPedidos.php'
        );
    }

    echo json_encode(['status' => 'success', 'message' => 'Estado del pedido actualizado correctamente.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>
