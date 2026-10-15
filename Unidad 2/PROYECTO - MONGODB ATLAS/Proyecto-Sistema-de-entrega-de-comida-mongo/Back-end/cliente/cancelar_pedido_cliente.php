<?php
// Back-end/cliente/cancelar_pedido_cliente.php
session_start();
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

if (!isset($_SESSION['id_cliente'])) {
    header("Location: ../../Front-end/login.php");
    exit;
}

$id_pedido = trim($_POST['idpedido_a_cancelar'] ?? '');

if (empty($id_pedido)) {
    $_SESSION['mensaje_perfil'] = "Error: Pedido no válido.";
    header("Location: MisPedidos.php");
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
            'id_vendedor'   => 1
        ]
    ]);

    if (!$pedido) {
        throw new Exception("El pedido no existe o ya no está disponible.");
    }

    $estadoActual = $pedido['estadopedido'] ?? '';
    if ($estadoActual !== 'Pendiente') {
        throw new Exception("No puedes cancelar este pedido porque ya está siendo procesado ($estadoActual). Contacta al vendedor.");
    }

    // 1. Devolver el stock a cada producto
    $items = (array)($pedido['items'] ?? []);
    foreach ($items as $item) {
        $idProd = $item['id_producto'] ?? null;
        $cant   = (int)($item['cantidad'] ?? 0);
        if ($idProd && $cant > 0) {
            $filtroP = [];
            if (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd)) {
                $filtroP = ['_id' => new MongoDB\BSON\ObjectId($idProd)];
            } elseif ($idProd instanceof MongoDB\BSON\ObjectId) {
                $filtroP = ['_id' => $idProd];
            } else {
                $filtroP = ['_id' => $idProd];
            }
            $colProductos->updateOne($filtroP, ['$inc' => ['stock_disponible' => $cant]]);
        }
    }

    // 2. Marcar pedido como Cancelado
    $colPedidos->updateOne(
        $filtroPedido,
        ['$set' => [
            'estadopedido'        => 'Cancelado',
            'notificado_vendedor' => 1,
            'notificado_cliente'  => 1
        ]]
    );

    // Notificar en tiempo real al vendedor sobre la cancelación
    if (!empty($pedido['id_vendedor'])) {
        require_once dirname(__DIR__) . '/servicios/NotificacionService.php';
        \Snackver\Servicios\NotificacionService::crearNotificacion(
            $pedido['id_vendedor'],
            'vendedor',
            "⚠️ El cliente ha cancelado su pedido.",
            (string)($pedido['id_pedido_num'] ?? $pedido['_id']),
            '../vendedor/Gestion_platillos.php'
        );
    }

    $mensajeExito = "El pedido ha sido cancelado exitosamente y el inventario ha sido restaurado.";
    $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($esAjax) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'message' => $mensajeExito, 'idpedido' => $id_pedido]);
        exit;
    }

    $_SESSION['mensaje_perfil'] = $mensajeExito;

} catch (Exception $e) {
    $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    if ($esAjax) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    $_SESSION['mensaje_perfil'] = "Error: " . $e->getMessage();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header("Location: MisPedidos.php");
exit;
?>