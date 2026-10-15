<?php
// Back-end/cliente/repetir_pedido.php
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

$idpedido_a_repetir = trim($_GET['idpedido'] ?? '');

if (empty($idpedido_a_repetir)) {
    header('Location: MisPedidos.php');
    exit();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos   = $mongoDb->selectCollection('pedidos');
    $colProductos = $mongoDb->selectCollection('productos');
    $colCarritos  = $mongoDb->selectCollection('carritos');

    $filtroPedido = [];
    if (is_string($idpedido_a_repetir) && preg_match('/^[a-f\d]{24}$/i', $idpedido_a_repetir)) {
        $filtroPedido = ['_id' => new MongoDB\BSON\ObjectId($idpedido_a_repetir)];
    } else {
        $filtroPedido = ['$or' => [
            ['id_pedido_num' => (int)$idpedido_a_repetir],
            ['_id' => (string)$idpedido_a_repetir],
            ['_id' => (int)$idpedido_a_repetir]
        ]];
    }

    $pedido = $colPedidos->findOne($filtroPedido);
    if (!$pedido || empty($pedido['items'])) {
        throw new Exception("No se encontró el pedido anterior o no contiene productos.");
    }

    // Filtro para el cliente
    $filtroCli = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtroCli = ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)];
    } else {
        $filtroCli = ['id_cliente' => $idClienteLogueado];
    }

    $cart = $colCarritos->findOne($filtroCli);
    $itemsEnCarrito = $cart ? (array)($cart['items'] ?? []) : [];

    $productos_agregados = 0;

    // Recolectar IDs de productos para consulta masiva en lote ($in)
    $prodIdsToFetch = [];
    foreach ($pedido['items'] as $item) {
        $idProd = $item['id_producto'] ?? null;
        if ($idProd) {
            $prodIdsToFetch[] = (is_string($idProd) && preg_match('/^[a-f\d]{24}$/i', $idProd)) 
                ? new MongoDB\BSON\ObjectId($idProd) : $idProd;
        }
    }

    $productosMap = [];
    if (!empty($prodIdsToFetch)) {
        $docsProd = $colProductos->find(['_id' => ['$in' => $prodIdsToFetch]]);
        foreach ($docsProd as $pDoc) {
            $productosMap[(string)$pDoc['_id']] = $pDoc;
        }
    }

    foreach ($pedido['items'] as $item) {
        $idProdStr = isset($item['id_producto']) ? (string)$item['id_producto'] : '';
        $cantDeseada = (int)($item['cantidad'] ?? 1);

        if (!isset($productosMap[$idProdStr])) continue;
        $prodDoc = $productosMap[$idProdStr];

        $stock = (int)($prodDoc['stock_disponible'] ?? ($prodDoc['disponible'] ?? 0));
        if ($stock <= 0) continue;

        $cantFinal = min($cantDeseada, $stock);
        $idProdStr = (string)$prodDoc['_id'];

        $encontrado = false;
        foreach ($itemsEnCarrito as &$cIt) {
            if ((string)$cIt['idproducto'] === $idProdStr) {
                $cIt['cantidad'] = min((int)$cIt['cantidad'] + $cantFinal, $stock);
                $encontrado = true;
                break;
            }
        }
        unset($cIt);

        if (!$encontrado) {
            $itemsEnCarrito[] = [
                'idproducto'     => $idProdStr,
                'nombreproducto' => $prodDoc['nombreproducto'] ?? 'Platillo',
                'imagen'         => $prodDoc['imagen'] ?? '',
                'precio'         => (float)($prodDoc['precio'] ?? 0),
                'cantidad'       => $cantFinal,
                'idvendedor'     => $prodDoc['id_vendedor'] ?? null
            ];
        }
        $productos_agregados++;
    }

    $colCarritos->updateOne(
        $filtroCli,
        ['$set' => [
            'id_cliente'          => $filtroCli['id_cliente'],
            'items'               => array_values($itemsEnCarrito),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ]],
        ['upsert' => true]
    );

    if ($productos_agregados > 0) {
        $_SESSION['mensaje_perfil'] = "Los platillos del pedido anterior se agregaron a tu carrito.";
    } else {
        $_SESSION['mensaje_perfil'] = "Los platillos del pedido anterior están agotados en este momento.";
    }

} catch (Exception $e) {
    $_SESSION['mensaje_perfil'] = "Error al repetir el pedido: " . $e->getMessage();
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

header('Location: carrito.php');
exit();
?>