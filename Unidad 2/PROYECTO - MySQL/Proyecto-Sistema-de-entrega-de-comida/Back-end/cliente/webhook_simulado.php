<?php
// Archivo: Back-end/api/webhook_simulado.php

$host = "127.0.0.1";
$user = "root";
$pass = "2884";
$db   = "sistemawebsnackver";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// 1. IMPORTANTE: Forzar UTF-8 para que la tilde de "preparación" no falle
$conn->set_charset("utf8mb4");

if (!isset($_GET['idpedido'])) {
    echo "❌ Faltó el ID. Usa ?idpedido=NUMERO";
    exit;
}

$id_pedido = intval($_GET['idpedido']);

// 2. LA ACTUALIZACIÓN
// ATENCIÓN AQUÍ: Verifica cómo está escrito EXACTAMENTE en tu base de datos.
// Opciones comunes: 'En preparación', 'En Preparación', 'Preparando'.
// Voy a usar 'En preparación' (con tilde), si tu BD es distinta, cámbialo aquí.

$nuevo_estado = "En preparación"; 

$sql = "UPDATE pedido 
        SET estadopedido = ?, 
            notificado_vendedor = 0 
        WHERE idpedido = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $nuevo_estado, $id_pedido);

if ($stmt->execute()) {
    // Verificar si realmente se afectó alguna fila
    if ($stmt->affected_rows > 0) {
        echo "<h1>✅ ¡Éxito Total!</h1>";
        echo "<p>Pedido #$id_pedido actualizado a <strong>'$nuevo_estado'</strong>.</p>";
    } else {
        echo "<h1>⚠️ Ojo:</h1>";
        echo "<p>La consulta se ejecutó, pero <strong>no se hicieron cambios</strong>.</p>";
        echo "<p>Posibles causas:</p>";
        echo "<ul>";
        echo "<li>El pedido #$id_pedido no existe.</li>";
        echo "<li>El pedido YA estaba en estado '$nuevo_estado' (no hubo cambio).</li>";
        echo "<li><strong>Revisa si tu base de datos acepta exactamente la palabra '$nuevo_estado'.</strong></li>";
        echo "</ul>";
    }
} else {
    echo "❌ Error SQL: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>