<?php
// Back-end/cliente/verificar_notificaciones.php
session_start();

// Limpiamos salida previa
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['id_cliente'])) {
    echo json_encode([]);
    exit;
}

$id_cliente = $_SESSION['id_cliente'];

// 1. OPTIMIZACIÓN CRÍTICA: Liberar la sesión.
// Ya tenemos el ID del cliente, no necesitamos mantener la sesión "abierta" y bloqueada
// mientras consultamos la base de datos. Esto hace que la web se sienta más rápida.
session_write_close();

include '../conexion/pantalla_usuario_cliente.php';
$conn->set_charset("utf8mb4");

// 2. CONSULTA (Igual que antes)
$sql = "SELECT idpedido, estadopedido 
        FROM pedido 
        WHERE idcliente = ? 
        AND notificado_cliente = 0 
        AND estadopedido IN ('En preparación', 'Listo', 'Entregado', 'Rechazado', 'Cancelado')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_cliente);
$stmt->execute();
$result = $stmt->get_result();

$notificaciones = [];
$ids_para_actualizar = []; // Array para guardar los IDs

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $estado = $row['estadopedido'];
        $mensaje = "";
        
        switch ($estado) {
            case 'En preparación':
                $mensaje = "👨‍🍳 ¡Tu pedido #" . $row['idpedido'] . " se está preparando!";
                break;
            case 'Listo':
                $mensaje = "✅ ¡Tu pedido #" . $row['idpedido'] . " está LISTO! Puedes pasar por él.";
                break;
            case 'Entregado':
                $mensaje = "🎉 ¡Pedido #" . $row['idpedido'] . " entregado! Gracias por tu compra.";
                break;
            case 'Rechazado':
                $mensaje = "❌ Lo sentimos, el pedido #" . $row['idpedido'] . " fue rechazado.";
                break;
            case 'Cancelado':
                $mensaje = "⚠️ El pedido #" . $row['idpedido'] . " ha sido cancelado.";
                break;
        }

        if (!empty($mensaje)) {
            $notificaciones[] = [
                'idpedido' => $row['idpedido'],
                'mensaje' => $mensaje
            ];
            
            // 2. OPTIMIZACIÓN: No actualizamos aquí, solo guardamos el ID en una lista
            $ids_para_actualizar[] = $row['idpedido'];
        }
    }
}

// 3. ACTUALIZACIÓN MASIVA (Una sola consulta en lugar de muchas)
if (!empty($ids_para_actualizar)) {
    // Convertimos el array [1, 2, 5] en string "1,2,5"
    $lista_ids = implode(',', $ids_para_actualizar);
    
    // Ejecutamos un solo UPDATE para todos esos IDs
    $sql_update = "UPDATE pedido SET notificado_cliente = 1 WHERE idpedido IN ($lista_ids)";
    $conn->query($sql_update);
}

echo json_encode($notificaciones);
$conn->close();
?>