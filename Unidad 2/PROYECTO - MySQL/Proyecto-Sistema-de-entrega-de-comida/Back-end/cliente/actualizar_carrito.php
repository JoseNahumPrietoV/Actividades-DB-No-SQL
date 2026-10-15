<?php

// Indicamos que la respuesta será JSON (muy importante para el JS)
header('Content-Type: application/json'); 

// Incluir conexión (Verifica que esta ruta sea correcta respecto a donde guardas este archivo)
include '../conexion/pantalla_usuario_cliente.php'; 

// 1. Verificar sesión
if (!isset($_SESSION['id_cliente'])) {
    echo json_encode(['status' => 'error', 'message' => 'No has iniciado sesión.']);
    exit;
}

$id_cliente = $_SESSION['id_cliente'];

// 2. Recibir datos del JS
$id_producto = isset($_POST['idproducto']) ? intval($_POST['idproducto']) : 0;
$accion = isset($_POST['accion']) ? $_POST['accion'] : '';

if ($id_producto <= 0 || empty($accion)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

// 3. Buscar el producto en el carrito
// OJO: Usamos detallecarrito unido con carrito para asegurar que pertenece al usuario
$sql_check = "SELECT dc.cantidad, dc.iddetallecarrito, p.precio, p.disponible 
              FROM detallecarrito dc
              JOIN carrito c ON dc.idcarrito = c.idcarrito
              JOIN producto p ON dc.idproducto = p.idproducto
              WHERE c.idcliente = ? AND dc.idproducto = ?";

$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_cliente, $id_producto);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Producto no encontrado en tu carrito.']);
    exit;
}

$item = $result_check->fetch_assoc();
$cantidad_actual = intval($item['cantidad']);
$stock_disponible = intval($item['disponible']); // Para validar si aumentas
$id_detalle = $item['iddetallecarrito'];

// 4. Calcular nueva cantidad
$nueva_cantidad = $cantidad_actual;

if ($accion === 'aumentar') {
    if ($cantidad_actual < $stock_disponible) {
        $nueva_cantidad++;
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No hay más stock disponible.']);
        exit;
    }

} elseif ($accion === 'disminuir') {
    
    // === CAMBIO IMPORTANTE AQUÍ ===
    // Solo restamos si la cantidad es mayor a 1.
    // Si es 1, se queda en 1. No permitimos que baje a 0 con este botón.
    if ($cantidad_actual > 1) {
        $nueva_cantidad--;
    } else {
        $nueva_cantidad = 1; // Se mantiene en 1
    }

} elseif ($accion === 'eliminar') {
    // Este botón SÍ pone la cantidad en 0 para borrarlo
    $nueva_cantidad = 0; 
}

// 5. Ejecutar actualización o borrado
if ($nueva_cantidad > 0) {
    // Actualizar
    $sql_update = "UPDATE detallecarrito SET cantidad = ? WHERE iddetallecarrito = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $nueva_cantidad, $id_detalle);
    $stmt_update->execute();
} else {
    // Borrar (si es 0 o eliminar)
    $sql_delete = "DELETE FROM detallecarrito WHERE iddetallecarrito = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("i", $id_detalle);
    $stmt_delete->execute();
}

// 6. Recalcular totales para actualizar la vista sin recargar
$sql_totales = "SELECT SUM(dc.cantidad * dc.precio) as subtotal 
                FROM detallecarrito dc
                JOIN carrito c ON dc.idcarrito = c.idcarrito
                WHERE c.idcliente = ?";
$stmt_totales = $conn->prepare($sql_totales);
$stmt_totales->bind_param("i", $id_cliente);
$stmt_totales->execute();
$res_totales = $stmt_totales->get_result()->fetch_assoc();

$nuevo_subtotal = floatval($res_totales['subtotal'] ?? 0);
$nuevo_total = $nuevo_subtotal; // Agrega envío o impuestos aquí si usas

// 7. Respuesta exitosa
echo json_encode([
    'status' => 'success',
    'nuevaCantidad' => $nueva_cantidad,
    'nuevosTotales' => [
        'subtotal' => number_format($nuevo_subtotal, 2),
        'total' => number_format($nuevo_total, 2)
    ]
]);

$conn->close();
?>