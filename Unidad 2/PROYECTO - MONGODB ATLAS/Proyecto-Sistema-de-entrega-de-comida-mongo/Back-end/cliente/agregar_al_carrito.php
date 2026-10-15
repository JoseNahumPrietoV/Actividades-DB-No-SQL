<?php
// Back-end/cliente/agregar_al_carrito.php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

if (!isset($_SESSION['id_cliente'])) { 
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para agregar productos.']);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$idproducto = $_POST['idproducto'] ?? '';
$precio_unitario = (float)($_POST['precio'] ?? 0.00);

if (empty($idproducto) || $precio_unitario <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos del producto no válidos.']);
    exit;
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colProductos = $mongoDb->selectCollection('productos');
    $colCarritos  = $mongoDb->selectCollection('carritos');

    // Buscar información del producto
    $filtroProd = [];
    if (is_string($idproducto) && preg_match('/^[a-f\d]{24}$/i', $idproducto)) {
        $filtroProd = ['_id' => new MongoDB\BSON\ObjectId($idproducto)];
    } else {
        $filtroProd = ['$or' => [['_id' => (int)$idproducto], ['_id' => (string)$idproducto]]];
    }

    $prodDoc = $colProductos->findOne($filtroProd);
    if (!$prodDoc) {
        echo json_encode(['status' => 'error', 'message' => 'El producto seleccionado no existe.']);
        exit;
    }

    $stock = (int)($prodDoc['stock_disponible'] ?? ($prodDoc['disponible'] ?? 0));
    if ($stock <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Lo sentimos, este producto está agotado.']);
        exit;
    }

    // Filtro para el cliente
    $filtroCli = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtroCli = ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)];
    } else {
        $filtroCli = ['id_cliente' => $idClienteLogueado];
    }

    $cart = $colCarritos->findOne($filtroCli);

    $idProdStr = (string)$prodDoc['_id'];

    if (!$cart) {
        // Crear carrito nuevo
        $nuevoCarrito = [
            'id_cliente'          => $filtroCli['id_cliente'],
            'items'               => [[
                'idproducto'     => $idProdStr,
                'nombreproducto' => $prodDoc['nombreproducto'] ?? 'Platillo',
                'imagen'         => $prodDoc['imagen'] ?? '',
                'precio'         => (float)($prodDoc['precio'] ?? $precio_unitario),
                'cantidad'       => 1,
                'idvendedor'     => $prodDoc['id_vendedor'] ?? null
            ]],
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ];
        $colCarritos->insertOne($nuevoCarrito);
    } else {
        $items = (array)($cart['items'] ?? []);
        $encontrado = false;

        foreach ($items as &$it) {
            if ((string)$it['idproducto'] === $idProdStr) {
                if ($it['cantidad'] < $stock) {
                    $it['cantidad'] = (int)$it['cantidad'] + 1;
                    $encontrado = true;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No puedes agregar más de las unidades en existencia.']);
                    exit;
                }
                break;
            }
        }
        unset($it);

        if (!$encontrado) {
            $items[] = [
                'idproducto'     => $idProdStr,
                'nombreproducto' => $prodDoc['nombreproducto'] ?? 'Platillo',
                'imagen'         => $prodDoc['imagen'] ?? '',
                'precio'         => (float)($prodDoc['precio'] ?? $precio_unitario),
                'cantidad'       => 1,
                'idvendedor'     => $prodDoc['id_vendedor'] ?? null
            ];
        }

        $colCarritos->updateOne(
            $filtroCli,
            ['$set' => [
                'items'               => array_values($items),
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ]]
        );
    }

    echo json_encode(['status' => 'success', 'message' => 'Producto agregado al carrito.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al agregar al carrito: ' . $e->getMessage()]);
}
?>