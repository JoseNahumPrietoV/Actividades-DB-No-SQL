<?php
// --- 1. INICIAR SESIÓN Y CONEXIÓN ---
header('Content-Type: application/json');
include '../conexion/pantalla_usuario_vendedor.php';

// --- 2. RECONECTAR A LA BASE DE DATOS ---
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";
$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Error de conexión.']);
    exit;
}

// --- 3. VALIDAR DATOS ---
$id_vendedor_logueado = $_SESSION['id_vendedor'] ?? 0;
if ($id_vendedor_logueado == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Usuario no logueado.']);
    exit;
}

$idproducto = $_POST['idproducto'] ?? 0;
if ($idproducto == 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID de producto no válido.']);
    exit;
}

$conn->begin_transaction();

try {
    // --- 4. OBTENER RUTA DE IMAGEN ANTES DE BORRAR ---
    $sql_get_img = "SELECT imagen FROM producto WHERE idproducto = ? AND idvendedor = ?";
    $stmt_get_img = $conn->prepare($sql_get_img);
    $stmt_get_img->bind_param("ii", $idproducto, $id_vendedor_logueado);
    $stmt_get_img->execute();
    $result_img = $stmt_get_img->get_result();
    
    if ($result_img->num_rows == 0) {
        throw new Exception('El producto no existe o no te pertenece.');
    }
    
    $fila_img = $result_img->fetch_assoc();
    $ruta_imagen_db = $fila_img['imagen']; // Ej: "img/12345_nombre.jpg"
    $stmt_get_img->close();

    // --- 5. BORRAR EL REGISTRO DE LA BASE DE DATOS ---
    $sql_delete = "DELETE FROM producto WHERE idproducto = ? AND idvendedor = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $idproducto, $id_vendedor_logueado);
    $stmt_delete->execute();
    
    if ($stmt_delete->affected_rows > 0) {
        // --- 6. SI SE BORRÓ DE LA BD, BORRAR EL ARCHIVO DE IMAGEN ---
        $ruta_absoluta_imagen = dirname(dirname(__DIR__)) . "/" . $ruta_imagen_db;
        if (file_exists($ruta_absoluta_imagen)) {
            unlink($ruta_absoluta_imagen); // Borra el archivo del servidor
        }
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Platillo eliminado.']);
    } else {
        throw new Exception('No se pudo eliminar el producto.');
    }
    
    $stmt_delete->close();

} catch (Exception $e) {
    $conn->rollback(); 
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>