<?php
// Back-end/cliente/procesar_pedido.php
session_start();
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php'; 

if (!isset($_SESSION['id_cliente'])) {
    header("Location: ../../Front-end/login.php");
    exit;
}

$metodo_pago = trim($_POST['metodopago'] ?? 'Efectivo');
$referencia  = trim($_POST['referenciapago'] ?? '');

try {
    $mongoDb = MongoConexion::getConexion();
    $colCarritos  = $mongoDb->selectCollection('carritos');
    $colProductos = $mongoDb->selectCollection('productos');
    $colPedidos   = $mongoDb->selectCollection('pedidos');

    $filtroCli = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtroCli = ['$or' => [
            ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)],
            ['id_cliente' => (string)$idClienteLogueado],
            ['id_cliente' => (int)$idClienteLogueado]
        ]];
    } else {
        $filtroCli = ['$or' => [
            ['id_cliente' => (int)$idClienteLogueado],
            ['id_cliente' => (string)$idClienteLogueado]
        ]];
    }

    $cartDoc = $colCarritos->findOne($filtroCli);
    if (!$cartDoc || empty($cartDoc['items'])) {
        throw new Exception("Tu carrito está vacío.");
    }

    $itemsCart = (array)$cartDoc['items'];
    $itemsEmbedded = [];
    $total_pedido = 0.00;
    $id_vendedor = null;

    // 1. Recolectar IDs de productos para consulta masiva en lote ($in) erradicando round-trips N+1
    $prodIdsToFetch = [];
    foreach ($itemsCart as $item) {
        $idProd = $item['idproducto'] ?? '';
        if ($idProd) {
            $prodIdsToFetch[] = (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd)) 
                ? new MongoDB\BSON\ObjectId($idProd) : $idProd;
        }
    }

    $productosMap = [];
    if (!empty($prodIdsToFetch)) {
        $docsProd = $colProductos->find(
            ['_id' => ['$in' => $prodIdsToFetch]],
            ['projection' => [
                'stock_disponible' => 1,
                'disponible'       => 1,
                'nombreproducto'   => 1,
                'id_vendedor'      => 1
            ]]
        );
        foreach ($docsProd as $pDoc) {
            $productosMap[(string)$pDoc['_id']] = $pDoc;
        }
    }

    // Validar Stock y construir lista embebida en memoria
    foreach ($itemsCart as $item) {
        $idProdStr = (string)($item['idproducto'] ?? '');
        $cant      = (int)($item['cantidad'] ?? 1);
        $prec      = (float)($item['precio'] ?? 0);

        if (!isset($productosMap[$idProdStr])) {
            throw new Exception("Uno de los productos en tu carrito ya no existe.");
        }

        $prodDoc = $productosMap[$idProdStr];
        $stockDisponible = (int)($prodDoc['stock_disponible'] ?? ($prodDoc['disponible'] ?? 0));
        if ($cant > $stockDisponible) {
            throw new Exception("Stock insuficiente para: " . ($prodDoc['nombreproducto'] ?? 'un platillo'));
        }

        if ($id_vendedor === null) {
            $id_vendedor = $prodDoc['id_vendedor'] ?? null;
        }

        $importe = $cant * $prec;
        $total_pedido += $importe;

        $itemsEmbedded[] = [
            'id_producto'     => $prodDoc['_id'],
            'nombreproducto'  => $prodDoc['nombreproducto'] ?? ($item['nombreproducto'] ?? 'Platillo'),
            'cantidad'        => $cant,
            'precio_unitario' => $prec,
            'importe'         => $importe
        ];
    }

    // 2. Insertar pedido en colección 'pedidos' de MongoDB Atlas
    $idPedidoNum = time();
    $docPedido = [
        'id_pedido_num'        => $idPedidoNum,
        'id_cliente'           => $cartDoc['id_cliente'],
        'id_vendedor'          => $id_vendedor,
        'items'                => $itemsEmbedded,
        'total'                => (float)$total_pedido,
        'estadopedido'         => 'Pendiente',
        'metodopago'           => $metodo_pago,
        'referenciapago'       => $referencia,
        'notificado_cliente'   => 1,
        'notificado_vendedor'  => 1,
        'fechapedido'          => new MongoDB\BSON\UTCDateTime()
    ];

    $colPedidos->insertOne($docPedido);

    // Crear notificación persistente en tiempo real para el vendedor
    require_once dirname(__DIR__) . '/servicios/NotificacionService.php';
    $msgVen = "🔔 ¡Nuevo pedido recibido! - $" . number_format($total_pedido, 2);
    \Snackver\Servicios\NotificacionService::crearNotificacion(
        $id_vendedor,
        'vendedor',
        $msgVen,
        (string)$idPedidoNum,
        '../vendedor/Gestion_platillos.php'
    );

    // 3. Descontar Stock atómicamente en MongoDB
    foreach ($itemsEmbedded as $emb) {
        $colProductos->updateOne(
            ['_id' => $emb['id_producto']],
            ['$inc' => ['stock_disponible' => -$emb['cantidad']]]
        );
    }

    // 4. Vaciar Carrito
    $colCarritos->deleteOne($filtroCli);

    $_SESSION['mensaje_perfil'] = "¡Pedido realizado con éxito! Tu orden está pendiente de confirmación.";
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header("Location: MisPedidos.php");
    exit;

} catch (Exception $e) {
    $_SESSION['mensaje_carrito'] = "Error al procesar el pedido: " . $e->getMessage();
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header("Location: carrito.php");
    exit;
}
?>