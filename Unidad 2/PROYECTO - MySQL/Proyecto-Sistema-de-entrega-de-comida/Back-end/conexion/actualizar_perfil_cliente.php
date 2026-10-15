<?php
session_start(); // Iniciar sesión primero

// --- 1. INCLUIR CONEXIÓN Y SESIÓN ---
// Incluimos el archivo que establece la conexión ($conn) y define $idClienteLogueado
include 'pantalla_usuario_cliente.php';

// Si la sesión no está activa, el include ya redirigió.
if ($idClienteLogueado == 0) {
    // Si el ID del cliente es 0, no está logueado (pantalla_usuario_cliente.php debió manejar esto)
    header('Location: ../../Front-end/login.php'); 
    exit();
}

// 2. RECIBIR Y SANITIZAR DATOS DEL FORMULARIO
$nombre = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$email = trim($_POST['email'] ?? '');

// Simple validación (deberías agregar validación más estricta si es necesario)
if (empty($nombre) || empty($apellido) || empty($telefono) || empty($email)) {
    $_SESSION['mensaje_perfil'] = 'Error: Todos los campos son obligatorios.';
    // Redirige a la página de perfil
    header('Location: ../cliente/Miperfil.php');
    exit();
}

// 3. PREPARAR LA CONSULTA DE ACTUALIZACIÓN
$sql_update = "UPDATE cliente 
               SET nombre_cliente = ?, apellido_cliente = ?, telefono_cliente = ?, email_cliente = ? 
               WHERE idCliente = ?";

$stmt_update = $conn->prepare($sql_update);

// Tipo de parámetros: ssss (string, string, string, string) por el idCliente (i)
$stmt_update->bind_param("ssssi", $nombre, $apellido, $telefono, $email, $idClienteLogueado);

// 4. EJECUTAR Y VERIFICAR
if ($stmt_update->execute()) {
    // Éxito: Se actualizó el perfil
    $_SESSION['mensaje_perfil'] = '✅ ¡Tu información se ha actualizado con éxito!';
    
    // Actualizar las variables de sesión para que reflejen los nuevos nombres
    $_SESSION['nombre_cliente'] = $nombre;
    $_SESSION['apellido_cliente'] = $apellido;
    
} else {
    // Error en la base de datos
    $_SESSION['mensaje_perfil'] = '❌ Error al actualizar la información: ' . $conn->error;
}

$stmt_update->close();
$conn->close();

// 5. REDIRIGIR AL PERFIL
header('Location: ../cliente/Miperfil.php');
exit();
?>