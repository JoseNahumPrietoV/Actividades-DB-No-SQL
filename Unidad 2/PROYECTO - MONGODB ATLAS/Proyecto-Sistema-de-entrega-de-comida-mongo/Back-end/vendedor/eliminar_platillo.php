<?php
// Back-end/vendedor/eliminar_platillo.php
header('Content-Type: application/json');
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';

$id_vendedor_logueado = $_SESSION['id_vendedor'] ?? 0;
if (empty($id_vendedor_logueado)) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no autenticado.']);
    exit;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    foreach ($_SESSION as $k => $v) {
        if (str_starts_with($k, 'snackver_cat_prods_')) {
            unset($_SESSION[$k]);
        }
    }
    session_write_close();
}

$idproducto = $_POST['idproducto'] ?? '';
if (empty($idproducto)) {
    echo json_encode(['status' => 'error', 'message' => 'ID de producto no válido.']);
    exit;
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colProductos = $mongoDb->selectCollection('productos');

    $filtro = [];
    if (is_string($idproducto) && preg_match('/^[a-f\d]{24}$/i', $idproducto)) {
        $filtro = ['_id' => new MongoDB\BSON\ObjectId($idproducto)];
    } else {
        $filtro = ['$or' => [['_id' => (int)$idproducto], ['_id' => (string)$idproducto]]];
    }

    $doc = $colProductos->findOne($filtro);
    if (!$doc) {
        echo json_encode(['status' => 'error', 'message' => 'El producto no existe o ya fue eliminado.']);
        exit;
    }

    // Si tiene imagen local, limpiarla
    $ruta_img = $doc['imagen'] ?? '';
    if (!empty($ruta_img) && !str_starts_with($ruta_img, 'http')) {
        $ruta_local = dirname(dirname(__DIR__)) . '/' . $ruta_img;
        if (file_exists($ruta_local)) {
            @unlink($ruta_local);
        }
    }

    $colProductos->deleteOne($filtro);
    echo json_encode(['status' => 'success', 'message' => 'Platillo eliminado con éxito.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>