<?php
// 1. INCLUIR LA CONEXIÓN Y SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// Configurar cabecera para JSON
header('Content-Type: application/json');

// 2. VALIDAR SESIÓN
if (!isset($_SESSION['id_vendedor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no iniciada']);
    exit;
}

// 3. RECIBIR DATOS
$id_pedido = isset($_POST['idpedido']) ? intval($_POST['idpedido']) : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? $_POST['nuevo_estado'] : '';

// 4. VALIDACIÓN BÁSICA
if ($id_pedido <= 0 || empty($nuevo_estado)) {
    echo json_encode(['status' => 'error', 'message' => 'Datos no válidos.']);
    exit;
}

// 5. RECONECTAR A LA BD (Por si el include no dejó la conexión abierta o para asegurar)
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión BD']);
    exit;
}

// --- INICIO DE TRANSACCIÓN (Para seguridad de datos) ---
$conn->begin_transaction();

try {

    // A. VERIFICAR EL ESTADO ACTUAL ANTES DE CAMBIARLO
    // Esto evita que si le das "Rechazar" dos veces, se sume el stock dos veces.
    $sql_check = "SELECT estadopedido FROM pedido WHERE idpedido = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $id_pedido);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $estado_actual = $res_check->fetch_assoc()['estadopedido'];
    $stmt_check->close();

    // Si ya estaba rechazado o cancelado, no hacemos nada con el stock, solo actualizamos estado por si acaso
    if ($estado_actual == 'Rechazado' || $estado_actual == 'Cancelado') {
        // Solo actualizamos el estado y salimos
        $devolver_stock = false;
    } else {
        // Si el pedido estaba "Pendiente" o "En preparación" y pasamos a "Rechazado", SÍ devolvemos stock
        if ($nuevo_estado == 'Rechazado' || $nuevo_estado == 'Cancelado') {
            $devolver_stock = true;
        } else {
            $devolver_stock = false;
        }
    }

    // B. LÓGICA DE DEVOLUCIÓN DE STOCK
    if ($devolver_stock) {
        // 1. Obtener los productos de ese pedido
        $sql_items = "SELECT idproducto, cantidad FROM detallepedido WHERE idpedido = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $id_pedido);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();

        // 2. Preparar la consulta para devolver stock
        $sql_restaurar = "UPDATE producto SET disponible = disponible + ? WHERE idproducto = ?";
        $stmt_restaurar = $conn->prepare($sql_restaurar);

        // 3. Recorrer y sumar
        while ($item = $result_items->fetch_assoc()) {
            $stmt_restaurar->bind_param("ii", $item['cantidad'], $item['idproducto']);
            $stmt_restaurar->execute();
        }
        $stmt_items->close();
        $stmt_restaurar->close();
    }

    // C. ACTUALIZAR EL ESTADO DEL PEDIDO
    $sql_update = "UPDATE pedido SET estadopedido = ?, notificado_cliente = 0 WHERE idpedido = ?";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $nuevo_estado, $id_pedido);

    if (!$stmt_update->execute()) {
        throw new Exception("Error al actualizar el estado del pedido.");
    }
    $stmt_update->close();

    // --- CONFIRMAR TODO ---
    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Estado actualizado y stock gestionado.']);
} catch (Exception $e) {
    // --- SI HUBO ERROR, DESHACER CAMBIOS ---
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
