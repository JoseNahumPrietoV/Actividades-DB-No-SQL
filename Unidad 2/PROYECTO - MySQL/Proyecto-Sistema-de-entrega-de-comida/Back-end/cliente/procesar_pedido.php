<?php
session_start();
include '../conexion/pantalla_usuario_cliente.php'; 

// Validar sesión
if (!isset($_SESSION['id_cliente'])) {
    header("Location: ../../login.php");
    exit;
}

$id_cliente = $_SESSION['id_cliente'];

// 1. Recibir datos del formulario
$metodo_pago = isset($_POST['metodopago']) ? $_POST['metodopago'] : 'Efectivo';
$referencia = isset($_POST['referenciapago']) ? $_POST['referenciapago'] : '';

$conn->begin_transaction();

try {
    // 2. Obtener productos del carrito
    $sql_carrito = "SELECT 
                        c.idcarrito, 
                        dc.idproducto, 
                        dc.cantidad, 
                        p.precio, 
                        p.nombreproducto, 
                        p.disponible, 
                        p.idvendedor
                    FROM detallecarrito dc
                    JOIN carrito c ON dc.idcarrito = c.idcarrito
                    JOIN producto p ON dc.idproducto = p.idproducto
                    WHERE c.idcliente = ?";
    
    $stmt_carrito = $conn->prepare($sql_carrito);
    $stmt_carrito->bind_param("i", $id_cliente);
    $stmt_carrito->execute();
    $resultado = $stmt_carrito->get_result();

    if ($resultado->num_rows === 0) {
        throw new Exception("Tu carrito está vacío.");
    }

    $productos_a_procesar = [];
    $total_pedido = 0;
    $id_vendedor = null; 
    $id_carrito_actual = null;

    // 3. Validar Stock y Calcular Total
    while ($row = $resultado->fetch_assoc()) {
        if ($id_carrito_actual === null) $id_carrito_actual = $row['idcarrito'];
        if ($id_vendedor === null) $id_vendedor = $row['idvendedor'];

        if ($row['cantidad'] > $row['disponible']) {
            throw new Exception("Stock insuficiente para: " . $row['nombreproducto']);
        }
        
        $total_pedido += ($row['precio'] * $row['cantidad']);
        $productos_a_procesar[] = $row;
    }

    // 4. Insertar el Pedido
    $fecha_actual = date('Y-m-d H:i:s');
    $sql_pedido = "INSERT INTO pedido (idcliente, idvendedor, fechapedido, total, estadopedido, metodopago, referenciapago) 
                   VALUES (?, ?, ?, ?, 'Pendiente', ?, ?)";
                   
    $stmt_pedido = $conn->prepare($sql_pedido);
    $stmt_pedido->bind_param("iisdss", $id_cliente, $id_vendedor, $fecha_actual, $total_pedido, $metodo_pago, $referencia);
    
    if (!$stmt_pedido->execute()) {
        throw new Exception("Error al guardar el pedido: " . $conn->error);
    }
    $id_nuevo_pedido = $conn->insert_id;

    // 5. Guardar Detalles y Restar Stock
    $sql_detalle = "INSERT INTO detallepedido (idpedido, idproducto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
    $stmt_detalle = $conn->prepare($sql_detalle);

    $sql_update_stock = "UPDATE producto SET disponible = disponible - ? WHERE idproducto = ?";
    $stmt_stock = $conn->prepare($sql_update_stock);

    foreach ($productos_a_procesar as $prod) {
        // Detalle
        $stmt_detalle->bind_param("iiid", $id_nuevo_pedido, $prod['idproducto'], $prod['cantidad'], $prod['precio']);
        $stmt_detalle->execute();

        // Stock
        $stmt_stock->bind_param("ii", $prod['cantidad'], $prod['idproducto']);
        $stmt_stock->execute();
    }

    // 6. Vaciar Carrito
    if ($id_carrito_actual !== null) {
        $stmt_del_det = $conn->prepare("DELETE FROM detallecarrito WHERE idcarrito = ?");
        $stmt_del_det->bind_param("i", $id_carrito_actual);
        $stmt_del_det->execute();

        $stmt_del_car = $conn->prepare("DELETE FROM carrito WHERE idcarrito = ?");
        $stmt_del_car->bind_param("i", $id_carrito_actual);
        $stmt_del_car->execute();
    }

    $conn->commit();

    $_SESSION['mensaje_perfil'] = "¡Pedido realizado con éxito!";
    header("Location: MisPedidos.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='carrito.php';</script>";
}
?>