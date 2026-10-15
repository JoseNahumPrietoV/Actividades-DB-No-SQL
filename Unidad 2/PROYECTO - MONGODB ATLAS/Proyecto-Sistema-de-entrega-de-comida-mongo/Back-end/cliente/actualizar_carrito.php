<?php
// Back-end/cliente/actualizar_carrito.php
header('Content-Type: application/json'); 
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php'; 

if (!isset($_SESSION['id_cliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'No has iniciado sesión.']);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$id_producto = $_POST['idproducto'] ?? '';
$accion = $_POST['accion'] ?? '';

if (empty($id_producto) || empty($accion)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colCarritos  = $mongoDb->selectCollection('carritos');
    $colProductos = $mongoDb->selectCollection('productos');

    $filtroCli = [];
    if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
        $filtroCli = ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)];
    } else {
        $filtroCli = ['id_cliente' => $idClienteLogueado];
    }

    $cart = $colCarritos->findOne($filtroCli);
    if (!$cart || empty($cart['items'])) {
        echo json_encode(['status' => 'error', 'message' => 'El carrito está vacío.']);
        exit;
    }

    // Buscar stock disponible en colección productos
    $filtroProd = [];
    if (is_string($id_producto) && preg_match('/^[a-f\d]{24}$/i', $id_producto)) {
        $filtroProd = ['_id' => new MongoDB\BSON\ObjectId($id_producto)];
    } else {
        $filtroProd = ['$or' => [['_id' => (int)$id_producto], ['_id' => (string)$id_producto]]];
    }
    $prodDoc = $colProductos->findOne($filtroProd);
    $stockMax = (int)($prodDoc['stock_disponible'] ?? ($prodDoc['disponible'] ?? 999));

    $items = (array)$cart['items'];
    $nueva_cantidad = 0;
    $itemsModificados = [];

    foreach ($items as $item) {
        $itArr = (array)$item;
        if ((string)$itArr['idproducto'] === (string)$id_producto) {
            $cant = (int)($itArr['cantidad'] ?? 1);
            if ($accion === 'aumentar') {
                if ($cant < $stockMax) {
                    $cant++;
                    $nueva_cantidad = $cant;
                    $itArr['cantidad'] = $cant;
                    $itemsModificados[] = $itArr;
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'No hay más stock disponible.']);
                    exit;
                }
            } elseif ($accion === 'disminuir') {
                $cant = ($cant > 1) ? $cant - 1 : 1;
                $nueva_cantidad = $cant;
                $itArr['cantidad'] = $cant;
                $itemsModificados[] = $itArr;
            } elseif ($accion === 'fijar') {
                $cantManual = max(1, (int)($_POST['cantidad'] ?? 1));
                if ($cantManual > $stockMax) {
                    $cantManual = $stockMax;
                }
                $nueva_cantidad = $cantManual;
                $itArr['cantidad'] = $cantManual;
                $itemsModificados[] = $itArr;
            } elseif ($accion === 'eliminar') {
                $nueva_cantidad = 0;
                // No se agrega a $itemsModificados (se elimina)
            }
        } else {
            $itemsModificados[] = $itArr;
        }
    }

    $colCarritos->updateOne(
        $filtroCli,
        ['$set' => [
            'items'               => array_values($itemsModificados),
            'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
        ]]
    );

    // Calcular nuevos totales
    $nuevo_subtotal = 0.00;
    foreach ($itemsModificados as $it) {
        $nuevo_subtotal += ((int)$it['cantidad'] * (float)$it['precio']);
    }

    echo json_encode([
        'status'        => 'success',
        'nuevaCantidad' => $nueva_cantidad,
        'nuevosTotales' => [
            'subtotal' => number_format($nuevo_subtotal, 2),
            'total'    => number_format($nuevo_subtotal, 2)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar: ' . $e->getMessage()]);
}
?>