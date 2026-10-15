<?php
// Back-end/vendedor/verificar_notificaciones_vendedor.php

// 1. Iniciar sesión para obtener el ID
session_start();

// Limpiamos cualquier salida previa
ob_clean(); 
header('Content-Type: application/json; charset=utf-8');

// 2. Verificar si hay vendedor logueado
if (!isset($_SESSION['id_vendedor'])) {
    echo json_encode([]);
    exit;
}

$id_vendedor = $_SESSION['id_vendedor'];

// Liberamos la sesión
session_write_close();

// 3. CONEXIÓN MANUAL
$host = "127.0.0.1"; 
$user = "root";
$pass = "2884";      
$db   = "sistemawebsnackver";

// Crear conexión usando MySQLi
mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $pass, $db);

// Verificamos conexión
if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión DB']); 
    exit;
}

$conn->set_charset("utf8mb4");

// 4. CONSULTA MODIFICADA
// AGREGAMOS 'En preparación' a la lista de estados permitidos
$sql = "SELECT idpedido, estadopedido, total 
        FROM pedido 
        WHERE idvendedor = ? 
        AND notificado_vendedor = 0 
        AND estadopedido IN ('Pendiente', 'Cancelado', 'En preparación')";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Error en SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id_vendedor);
$stmt->execute();
$result = $stmt->get_result();

$notificaciones = [];
$ids_para_actualizar = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $estado = $row['estadopedido'];
        $mensaje = "";
        
        switch ($estado) {
            case 'Pendiente':
                $mensaje = "🔔 ¡Nuevo pedido recibido! (#" . $row['idpedido'] . ") - $" . $row['total'];
                break;
                
            case 'Cancelado':
                $mensaje = "⚠️ El cliente ha cancelado el pedido #" . $row['idpedido'];
                break;

            // --- NUEVO CASO AGREGADO PARA EL PAGO ---
            case 'En preparación':
                $mensaje = "💰 ¡Pago confirmado! El pedido #" . $row['idpedido'] . " ya fue pagado.";
                break;
        }

        if (!empty($mensaje)) {
            $notificaciones[] = [
                'idpedido' => $row['idpedido'],
                'mensaje' => $mensaje,
                'tipo' => $estado 
            ];
            $ids_para_actualizar[] = $row['idpedido'];
        }
    }
}

// 5. ACTUALIZAR ESTADO (Marcar como vistos)
if (!empty($ids_para_actualizar)) {
    $lista_ids = implode(',', $ids_para_actualizar);
    $sql_update = "UPDATE pedido SET notificado_vendedor = 1 WHERE idpedido IN ($lista_ids)";
    $conn->query($sql_update);
}

echo json_encode($notificaciones);

$stmt->close();
$conn->close();
?>