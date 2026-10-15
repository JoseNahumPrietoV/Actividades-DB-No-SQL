<?php
session_start();
include '../conexion/pantalla_usuario_cliente.php';

// 1. Verificar sesión
if (!isset($_SESSION['id_cliente'])) {
    header("Location: ../../login.php");
    exit;
}

$id_cliente = $_SESSION['id_cliente'];
$id_pedido = isset($_POST['idpedido_a_cancelar']) ? intval($_POST['idpedido_a_cancelar']) : 0;

if ($id_pedido <= 0) {
    $_SESSION['mensaje_perfil'] = "Error: Pedido no válido.";
    header("Location: ../../Front-end/cliente/MisPedidos.php");
    exit;
}

// --- INICIO TRANSACCIÓN ---
$conn->begin_transaction();

try {
    // 2. VERIFICAR EL PEDIDO
    // Solo permitimos cancelar si es del cliente Y está en estado 'Pendiente'
    $sql_check = "SELECT estadopedido FROM pedido WHERE idpedido = ? AND idcliente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_pedido, $id_cliente);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if ($res_check->num_rows === 0) {
        throw new Exception("El pedido no existe o no te pertenece.");
    }

    $fila = $res_check->fetch_assoc();
    if ($fila['estadopedido'] !== 'Pendiente') {
        throw new Exception("No puedes cancelar este pedido porque ya está siendo procesado. Contacta al vendedor.");
    }
    $stmt_check->close();

    // 3. DEVOLVER EL STOCK (La parte importante)
    // A. Obtenemos los productos del pedido
    $sql_items = "SELECT idproducto, cantidad FROM detallepedido WHERE idpedido = ?";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bind_param("i", $id_pedido);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    // B. Preparamos la consulta para sumar stock
    $sql_restaurar = "UPDATE producto SET disponible = disponible + ? WHERE idproducto = ?";
    $stmt_restaurar = $conn->prepare($sql_restaurar);

    // C. Recorremos y devolvemos
    while ($item = $result_items->fetch_assoc()) {
        $stmt_restaurar->bind_param("ii", $item['cantidad'], $item['idproducto']);
        $stmt_restaurar->execute();
    }
    $stmt_items->close();
    $stmt_restaurar->close();

    // 4. CAMBIAR ESTADO A 'CANCELADO' (CORREGIDO)
    // Aquí cambiamos '$id_pedido' por '?' para que funcione el bind_param
    $sql_cancelar = "UPDATE pedido 
        SET estadopedido = 'Cancelado', 
            notificado_vendedor = 0, 
            notificado_cliente = 1 
        WHERE idpedido = ?"; 
        
    $stmt_cancelar = $conn->prepare($sql_cancelar);
    $stmt_cancelar->bind_param("i", $id_pedido);

    if (!$stmt_cancelar->execute()) {
        throw new Exception("Error al actualizar el estado del pedido.");
    }
    $stmt_cancelar->close();

    // --- CONFIRMAR ---
    $conn->commit();

    $_SESSION['mensaje_perfil'] = "El pedido #$id_pedido ha sido cancelado exitosamente.";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['mensaje_perfil'] = "Error: " . $e->getMessage();
}

$conn->close();

// 5. REDIRIGIR
// Ajusté la ruta para que sea consistente con la del error (al Front-end)
header("Location: MisPedidos.php"); 
exit;
?>