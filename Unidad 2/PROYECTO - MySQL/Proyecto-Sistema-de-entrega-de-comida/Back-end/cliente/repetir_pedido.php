<?php
// --- 1. INCLUIR CONEXIÓN Y SESIÓN ---
include '../conexion/pantalla_usuario_cliente.php';

$idpedido_a_repetir = isset($_GET['idpedido']) ? intval($_GET['idpedido']) : 0;

if ($idClienteLogueado == 0) {
    header('Location: login.php');
    exit();
}

if ($idpedido_a_repetir == 0) {
    header('Location: MisPedidos.php');
    exit();
}

$conn->begin_transaction();

try {
    // --- A. OBTENER O CREAR CARRITO ---
    // (Esta parte es igual a tu código original, la resumo)
    $idcarrito = 0;
    $sql_find_cart = "SELECT idcarrito FROM carrito WHERE idcliente = ?";
    $stmt_find = $conn->prepare($sql_find_cart);
    $stmt_find->bind_param("i", $idClienteLogueado);
    $stmt_find->execute();
    $result_cart = $stmt_find->get_result();

    if ($result_cart->num_rows > 0) {
        $idcarrito = $result_cart->fetch_assoc()['idcarrito'];
    } else {
        $sql_create_cart = "INSERT INTO carrito (idcliente, subtotal, fechaagregado) VALUES (?, 0.00, NOW())";
        $stmt_create = $conn->prepare($sql_create_cart);
        $stmt_create->bind_param("i", $idClienteLogueado);
        $stmt_create->execute();
        $idcarrito = $conn->insert_id;
        $stmt_create->close();
    }
    $stmt_find->close();

    // --- B. OBTENER PRODUCTOS DEL PEDIDO ANTERIOR CON SU STOCK ACTUAL ---
    // Es vital traer 'p.disponible' para saber si podemos venderlo de nuevo
    $sql_pedido_items = "
    SELECT 
        dp.idproducto,
        dp.cantidad AS cantidad_deseada,
        p.precio,
        p.disponible AS stock_actual
    FROM detallepedido dp
    JOIN producto p ON dp.idproducto = p.idproducto
    WHERE dp.idpedido = ?";

    $stmt_items = $conn->prepare($sql_pedido_items);
    $stmt_items->bind_param("i", $idpedido_a_repetir);
    $stmt_items->execute();
    $resultado_items = $stmt_items->get_result();
    $stmt_items->close();

    if ($resultado_items->num_rows == 0) {
        throw new Exception("No se encontraron productos válidos.");
    }

    // --- C. AGREGAR AL CARRITO (CON VALIDACIÓN DE STOCK) ---
    $productos_agregados = 0;
    $productos_sin_stock = 0;

    while ($item = $resultado_items->fetch_assoc()) {
        $idproducto = $item['idproducto'];
        $cantidad_deseada = $item['cantidad_deseada'];
        $stock_actual = $item['stock_actual'];
        $precio_actual = $item['precio'];

        // 1. Calcular cuánto podemos agregar realmente
        $cantidad_final = 0;

        if ($stock_actual >= $cantidad_deseada) {
            // Hay suficiente para todo
            $cantidad_final = $cantidad_deseada;
        } elseif ($stock_actual > 0) {
            // Hay algo, pero no todo (llevamos lo que haya)
            $cantidad_final = $stock_actual;
        } else {
            // No hay nada, saltamos este producto
            $productos_sin_stock++;
            continue; 
        }

        // 2. Insertar o Actualizar en el carrito
        // Primero revisamos si ya estaba en el carrito para no duplicar fila
        $sql_check = "SELECT iddetallecarrito, cantidad FROM detallecarrito WHERE idcarrito = ? AND idproducto = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("ii", $idcarrito, $idproducto);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Ya estaba en el carrito, sumamos
            $detalle = $result_check->fetch_assoc();
            $nueva_cantidad_total = $detalle['cantidad'] + $cantidad_final;
            
            // Doble chequeo: que la suma no supere el stock
            if ($nueva_cantidad_total > $stock_actual) {
                $nueva_cantidad_total = $stock_actual;
            }

            $sql_update = "UPDATE detallecarrito SET cantidad = ? WHERE iddetallecarrito = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ii", $nueva_cantidad_total, $detalle['iddetallecarrito']);
            $stmt_update->execute();
            $stmt_update->close();
        } else {
            // Nuevo en el carrito
            $sql_insert = "INSERT INTO detallecarrito (idcarrito, idproducto, cantidad, precio) VALUES (?, ?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("iiid", $idcarrito, $idproducto, $cantidad_final, $precio_actual);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        $stmt_check->close();
        $productos_agregados++;
    }

    // --- D. RECALCULAR SUBTOTAL ---
    $sql_recalc = "UPDATE carrito c SET c.subtotal = (
                        SELECT COALESCE(SUM(dc.cantidad * dc.precio), 0) 
                        FROM detallecarrito dc 
                        WHERE dc.idcarrito = ?
                    ) WHERE c.idcarrito = ?";
    $stmt_recalc = $conn->prepare($sql_recalc);
    $stmt_recalc->bind_param("ii", $idcarrito, $idcarrito);
    $stmt_recalc->execute();
    $stmt_recalc->close();

    $conn->commit();
    
    // Mensaje inteligente para el usuario
    if ($productos_agregados > 0) {
        $msg = "Se agregaron los productos del pedido #$idpedido_a_repetir al carrito.";
        if ($productos_sin_stock > 0) {
            $msg .= " (Algunos productos no tenían stock suficiente y se ajustaron).";
        }
        $_SESSION['mensaje_carrito'] = $msg;
    } else {
        $_SESSION['mensaje_carrito'] = "No se pudieron agregar productos (sin stock disponible).";
    }

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['mensaje_carrito'] = "Error: " . $e->getMessage();
}

$conn->close();
header('Location: carrito.php');
exit();
?>