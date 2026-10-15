<?php
session_start();

// 1. Conexión a la Base de Datos
$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "2884";
$base_de_datos = "sistemawebsnackver";

$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);
if ($conexion->connect_error) {
    die("Conexión fallida.");
}

// 2. Recibir datos
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiamos el correo entrante
    $email = strtolower(trim($_POST['email_recuperacion'] ?? ''));
    $pass1 = $_POST['nueva_password'];
    $pass2 = $_POST['confirm_password'];
    $mensaje = "";

    // 3. Validar
    if (empty($email) || empty($pass1) || empty($pass2)) {
        $mensaje = "Todos los campos son obligatorios.";
    } elseif ($pass1 !== $pass2) {
        $mensaje = "Las contraseñas no coinciden.";
    } elseif (strlen($pass1) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        
        // 4. ENCRIPTAR
        $pass_hash = password_hash($pass1, PASSWORD_DEFAULT);
        
        $actualizado = false;

        // --- A. ACTUALIZAR CLIENTE ---
        // USAMOS LOWER(TRIM()) PARA ASEGURAR QUE ENCUENTRE EL CORREO
        $sql_cte = "UPDATE cliente 
                    SET password_cliente = ?, confirm_password_cliente = ? 
                    WHERE LOWER(TRIM(email_cliente)) = ?";
        
        if ($stmt = $conexion->prepare($sql_cte)) {
            $stmt->bind_param("sss", $pass_hash, $pass1, $email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $actualizado = true;
            $stmt->close();
        }

        // --- B. ACTUALIZAR VENDEDOR ---
        $sql_ven = "UPDATE vendedor 
                    SET password_vendedor = ?, confirm_password_vendedor = ? 
                    WHERE LOWER(TRIM(email_vendedor)) = ?";
        
        if ($stmt = $conexion->prepare($sql_ven)) {
            $stmt->bind_param("sss", $pass_hash, $pass1, $email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $actualizado = true;
            $stmt->close();
        }

        // --- 5. VERIFICAR RESULTADO Y REDIRIGIR ---
        if ($actualizado) {
            // CASO ÉXITO: Redirigir al LOGIN
            $_SESSION['login_success'] = "¡Contraseña actualizada con éxito! Por favor inicia sesión.";
            header("Location: ../../Front-end/login.php");
            exit();
        } else {
            // CASO FALLO: Mensaje de error específico
            $mensaje = "Error: No se encontró ese correo o intentaste poner la misma contraseña que ya tenías.";
        }
    }

    // SI LLEGA AQUÍ ES PORQUE HUBO UN ERROR (Validación o SQL)
    // Redirigir de vuelta al formulario de recuperación para mostrar el error
    $_SESSION['resultado_cambio'] = $mensaje;
    header("Location: ../../Front-end/recuperacion_password.php"); 
    exit();
}
?>