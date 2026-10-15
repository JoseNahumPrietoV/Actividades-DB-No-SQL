<?php
// Iniciar la sesión AQUÍ, al principio de todo.
session_start(); 

date_default_timezone_set('America/Mexico_City');

// 1. **VERIFICAR SESIÓN** y obtener el ID del usuario
if (!isset($_SESSION['id_cliente'])) {
    // Si el usuario no está logueado, redirigir a la página de inicio de sesión.
    header('Location: login.php'); // Asegúrate que 'login.php' sea tu página de login
    exit();
} 

// ¡CAMBIO CLAVE! Usaremos $idClienteLogueado en todo el sistema.
$idClienteLogueado = $_SESSION['id_cliente']; 
$datos_cliente = []; // Array para almacenar los datos del cliente

// 2. **CONEXIÓN Y CONSULTA** a la base de datos
// --- Configuración de la conexión a la base de datos ---
$host = "127.0.0.1"; 
$db_user = "root";   
$db_pass = "2884";       
$db_name = "sistemawebsnackver"; 

// Crear conexión
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 3. Preparar y ejecutar la consulta
$sql = "SELECT nombre_cliente, apellido_cliente, telefono_cliente, email_cliente FROM cliente WHERE idCliente = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // ¡CAMBIO CLAVE! Usar la variable estandarizada.
    $stmt->bind_param("i", $idClienteLogueado); // "i" es para tipo entero
    
    // Ejecutar la consulta
    $stmt->execute();
    
    // Obtener el resultado
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        // Almacenar los datos del cliente en el array
        $datos_cliente = $resultado->fetch_assoc();
    } else {
        echo "<p style='color: red;'>Error: No se encontró la información del cliente.</p>";
    }
    
    // Cerrar la declaración
    $stmt->close();
} else {
    echo "<p style='color: red;'>Error al preparar la consulta: " . $conn->error . "</p>";
}

// NO CIERRES la conexión $conn aquí. Deja que los scripts que la incluyen la cierren al final.
// $conn->close(); 
?>