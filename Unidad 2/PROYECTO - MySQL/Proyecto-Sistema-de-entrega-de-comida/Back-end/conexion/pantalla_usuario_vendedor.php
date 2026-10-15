<?php
// ¡Recuerda iniciar la sesión al principio de tu página!
session_start(); 

date_default_timezone_set('America/Mexico_City');

// 1. **VERIFICAR SESIÓN** y obtener el ID del vendedor
if (!isset($_SESSION['id_vendedor'])) {
    // Si el vendedor no está logueado, redirigir a la página de inicio de sesión.
    // Asegúrate de cambiar 'login_vendedor.php' por tu página de login específica para vendedores
    header('Location: login.php'); 
    exit();
}

// Obtener el ID del vendedor logueado
$id_vendedor = $_SESSION['id_vendedor'];
$datos_vendedor = []; // Array para almacenar los datos del vendedor

// 2. **CONEXIÓN Y CONSULTA** a la base de datos
// --- Configuración de la conexión a la base de datos ---
$host = "127.0.0.1"; // O el host de tu base de datos
$db_user = "root";   // Tu usuario de la base de datos
$db_pass = "2884";       // Tu contraseña de la base de datos
$db_name = "sistemawebsnackver"; // El nombre de tu BD

// Crear conexión
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// 3. Preparar y ejecutar la consulta
// Se seleccionan las columnas que NO son sensibles (sin password, confirm_password ni estado)
$sql = "SELECT nombre_vendedor, apellido_vendedor, telefono_vendedor, email_vendedor, nombre_negocio, descripcion, dirección, fechaRegistro FROM vendedor WHERE idVendedor = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    // Enlazar el ID del vendedor
    $stmt->bind_param("i", $id_vendedor); // "i" es para tipo entero

    // Ejecutar la consulta
    $stmt->execute();

    // Obtener el resultado
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        // Almacenar los datos del vendedor en el array asociativo
        $datos_vendedor = $resultado->fetch_assoc();
    } else {
        // Manejar caso donde el ID no se encuentra (puede ser un error de seguridad o BD)
        echo "<p style='color: red;'>Error: No se encontró la información del vendedor.</p>";
        // Opcional: Cerrar sesión y redirigir
        // session_destroy(); 
        // header('Location: login_vendedor.php');
        // exit();
    }

    // Cerrar la declaración
    $stmt->close();
} else {
    echo "<p style='color: red;'>Error al preparar la consulta: " . $conn->error . "</p>";
}

// Cerrar la conexión
$conn->close();

// Ahora la variable **$datos_vendedor** contiene los datos del vendedor logueado.
// Puedes acceder a ellos así:
/*
echo "Nombre: " . $datos_vendedor['nombre_vendedor'] . "<br>";
echo "Negocio: " . $datos_vendedor['nombre_negocio'] . "<br>";
*/
?>