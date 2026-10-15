<?php
// --- 1. INICIAR SESIÓN Y CONEXIÓN (EL MODO CORRECTO) ---
header('Content-Type: application/json'); // Dejamos esto

// ¡INCLUIMOS EL MISMO ARCHIVO QUE TUS OTRAS PÁGINAS!
// Este archivo ya inicia la sesión y nos da $conn y $idClienteLogueado
include '../conexion/pantalla_usuario_cliente.php';

// --- 2. VALIDAR DATOS ---
// La conexión $conn ya viene del include.

// Validar que el cliente esté logueado
// ¡Usamos la variable $idClienteLogueado que viene del include!
if ($idClienteLogueado == 0) { 
    echo json_encode(['status' => 'error', 'message' => 'Debes iniciar sesión para agregar productos.']);
    exit;
}

// Validar que recibimos los datos del producto
$idproducto = $_POST['idproducto'] ?? 0;
$precio_unitario = $_POST['precio'] ?? 0.00;
if ($idproducto == 0 || $precio_unitario <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos del producto no válidos.']);
    exit;
}

$conn->begin_transaction(); // Iniciar transacción (para seguridad)

try {
    // --- 3. ENCONTRAR O CREAR UN CARRITO ACTIVO ---
    $sql_find_cart = "SELECT idcarrito FROM carrito WHERE idcliente = ?";
    $stmt_find = $conn->prepare($sql_find_cart);
    // ¡Usamos $idClienteLogueado aquí también!
    $stmt_find->bind_param("i", $idClienteLogueado); 
    $stmt_find->execute();
    $result_cart = $stmt_find->get_result();
    
    $idcarrito = 0;
    if ($result_cart->num_rows > 0) {
        $idcarrito = $result_cart->fetch_assoc()['idcarrito'];
    } else {
        // No hay carrito, creamos uno nuevo
        $sql_create_cart = "INSERT INTO carrito (idcliente, subtotal, fechaagregado) VALUES (?, 0.00, NOW())";
        $stmt_create = $conn->prepare($sql_create_cart);
        // ¡Y aquí!
        $stmt_create->bind_param("i", $idClienteLogueado); 
        $stmt_create->execute();
        $idcarrito = $conn->insert_id;
        $stmt_create->close();
    }
    $stmt_find->close();

    // --- 4. AÑADIR/ACTUALIZAR PRODUCTO EN 'detallecarrito' ---
    // (El resto del script funciona igual, solo asegúrate de que use $idcarrito)
    
    $sql_find_item = "SELECT iddetallecarrito, cantidad FROM detallecarrito WHERE idcarrito = ? AND idproducto = ?";
    $stmt_find_item = $conn->prepare($sql_find_item);
    $stmt_find_item->bind_param("ii", $idcarrito, $idproducto);
    $stmt_find_item->execute();
    $result_item = $stmt_find_item->get_result();

    if ($result_item->num_rows > 0) {
        // El producto YA ESTÁ: actualizamos la cantidad
        $item = $result_item->fetch_assoc();
        $nueva_cantidad = $item['cantidad'] + 1;
        $sql_update_item = "UPDATE detallecarrito SET cantidad = ? WHERE iddetallecarrito = ?";
        $stmt_update_item = $conn->prepare($sql_update_item);
        $stmt_update_item->bind_param("ii", $nueva_cantidad, $item['iddetallecarrito']);
        $stmt_update_item->execute();
        $stmt_update_item->close();
    } else {
        // El producto ES NUEVO: lo insertamos
        $sql_insert_item = "INSERT INTO detallecarrito (idcarrito, idproducto, cantidad, precio) VALUES (?, ?, 1, ?)";
        $stmt_insert_item = $conn->prepare($sql_insert_item);
        $stmt_insert_item->bind_param("iid", $idcarrito, $idproducto, $precio_unitario);
        $stmt_insert_item->execute();
        $stmt_insert_item->close();
    }
    $stmt_find_item->close();

    // --- 5. RECALCULAR EL SUBTOTAL DEL CARRITO ---
    $sql_recalc = "UPDATE carrito c SET c.subtotal = (
                       SELECT SUM(dc.cantidad * dc.precio) 
                       FROM detallecarrito dc 
                       WHERE dc.idcarrito = ?
                   ) WHERE c.idcarrito = ?";
    $stmt_recalc = $conn->prepare($sql_recalc);
    $stmt_recalc->bind_param("ii", $idcarrito, $idcarrito);
    $stmt_recalc->execute();
    $stmt_recalc->close();

    // --- 6. TERMINAR Y RESPONDER ---
    $conn->commit(); 
    echo json_encode(['status' => 'success', 'message' => 'Producto agregado.']);

} catch (Exception $e) {
    $conn->rollback(); 
    echo json_encode(['status' => 'error', 'message' => 'Error en la transacción: ' . $e->getMessage()]);
}

$conn->close();
?>