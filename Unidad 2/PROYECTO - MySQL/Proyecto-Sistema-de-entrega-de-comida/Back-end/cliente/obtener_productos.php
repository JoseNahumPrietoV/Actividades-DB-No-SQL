<?php
// Back-end/cliente/obtener_productos.php
include '../conexion/pantalla_usuario_cliente.php';

$id_vendedor = isset($_GET['idvendedor']) ? intval($_GET['idvendedor']) : 0;

$productos = [];

if ($id_vendedor > 0) {
    $sql = "SELECT idproducto, nombreproducto FROM producto WHERE idvendedor = ? AND disponible > 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_vendedor);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
    }
    $stmt->close();
}

// Devolver JSON para que Javascript lo lea
header('Content-Type: application/json');
echo json_encode($productos);
$conn->close();
?>