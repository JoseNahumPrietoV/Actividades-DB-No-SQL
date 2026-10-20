<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'dev') {
    header('Location: index.php');
    exit();
}

// Configuración de la base de datos
$servername = "localhost";
$username = "tienda_user";
$password = "password123";
$dbname = "tienda_videojuegos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$userId = $_SESSION['user_id'];
$gameId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($gameId <= 0) {
    $_SESSION['error'] = "ID de juego no válido";
    header('Location: developer_panel.php');
    exit();
}

// Verificar que el juego pertenezca al desarrollador
$checkStmt = $conn->prepare("SELECT id, titulo FROM juegos WHERE id = ? AND desarrollador_id = ?");
$checkStmt->bind_param("ii", $gameId, $userId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    $_SESSION['error'] = "No tienes permiso para eliminar este juego";
    header('Location: developer_panel.php');
    exit();
}

$game = $checkResult->fetch_assoc();
$gameTitle = $game['titulo'];
$checkStmt->close();

// Iniciar transacción
$conn->begin_transaction();

try {
    // Eliminar dependencias
    $tables = ['reseñas', 'biblioteca', 'carrito', 'ventas'];
    foreach ($tables as $table) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE juego_id = ?");
        $stmt->bind_param("i", $gameId);
        $stmt->execute();
        $stmt->close();
    }
    
    // Eliminar el juego
    $stmt = $conn->prepare("DELETE FROM juegos WHERE id = ?");
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $conn->commit();
        $_SESSION['success'] = "Juego '$gameTitle' eliminado exitosamente";
    } else {
        $conn->rollback();
        $_SESSION['error'] = "Error al eliminar el juego";
    }
    
    $stmt->close();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

$conn->close();

// Redirigir de vuelta al panel
header('Location: developer_panel.php');
exit();
?>