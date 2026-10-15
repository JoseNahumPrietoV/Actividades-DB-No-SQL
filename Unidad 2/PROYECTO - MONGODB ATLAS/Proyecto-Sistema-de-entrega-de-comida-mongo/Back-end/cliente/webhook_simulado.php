<?php
// Back-end/cliente/webhook_simulado.php - Pure MongoDB Atlas
require_once __DIR__ . '/../conexion/conexion_mongo.php';
require_once __DIR__ . '/../servicios/NotificacionService.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_GET['idpedido'])) {
    echo "❌ Faltó el identificador del pedido.";
    exit;
}

$id_pedido = trim($_GET['idpedido']);
$nuevo_estado = "En preparación";

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos = $mongoDb->selectCollection('pedidos');

    $orConditions = [
        ['id_pedido_num' => (int)$id_pedido],
        ['id_pedido_num' => (string)$id_pedido],
        ['_id' => (string)$id_pedido]
    ];
    if (preg_match('/^[a-f\d]{24}$/i', $id_pedido)) {
        $orConditions[] = ['_id' => new MongoDB\BSON\ObjectId($id_pedido)];
    }
    $filtro = ['$or' => $orConditions];

    $pedido = $colPedidos->findOne($filtro);

    if (!$pedido) {
        echo "<h1>⚠️ Atención:</h1>";
        echo "<p>No se encontró el pedido solicitado.</p>";
        exit;
    }

    $updateRes = $colPedidos->updateOne(
        $filtro,
        ['$set' => [
            'estadopedido'        => $nuevo_estado,
            'notificado_vendedor' => 1,
            'notificado_cliente'  => 1
        ]]
    );

    // Crear notificación persistente en tiempo real para cliente y vendedor (sin #id)
    if (!empty($pedido['id_cliente'])) {
        \Snackver\Servicios\NotificacionService::crearNotificacion(
            $pedido['id_cliente'],
            'cliente',
            "👨‍🍳 ¡Tu pedido se está preparando!",
            (string)($pedido['id_pedido_num'] ?? $pedido['_id']),
            '../cliente/MisPedidos.php'
        );
    }

    if (!empty($pedido['id_vendedor'])) {
        \Snackver\Servicios\NotificacionService::crearNotificacion(
            $pedido['id_vendedor'],
            'vendedor',
            "💰 ¡Pago confirmado! El pedido se encuentra en preparación.",
            (string)($pedido['id_pedido_num'] ?? $pedido['_id']),
            '../vendedor/Gestion_platillos.php'
        );
    }

    echo "<h1>✅ ¡Pago Confirmado con Éxito!</h1>";
    echo "<p>El pedido fue actualizado a <strong>'$nuevo_estado'</strong>.</p>";

} catch (Exception $e) {
    echo "❌ Error al procesar el pedido: " . htmlspecialchars($e->getMessage());
}