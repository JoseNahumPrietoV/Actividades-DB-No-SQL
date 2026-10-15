<?php
// admin/actualizar_estado_cliente.php
header('Content-Type: application/json');

// Conexión
$conn = new mysqli("127.0.0.1", "root", "2884", "sistemawebsnackver");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$estado = isset($_POST['estado']) ? $_POST['estado'] : '';

if ($id > 0 && ($estado == 'activo' || $estado == 'inactivo')) {
    
    $sql = "UPDATE cliente SET estado = ? WHERE idCliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $estado, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
}

$conn->close();
?>