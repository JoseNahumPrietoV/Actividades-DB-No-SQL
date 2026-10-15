<?php
session_start();

// --- Configuración de la Conexión a la Base de Datos ---
$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "2884";
$base_de_datos = "sistemawebsnackver";

$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- Procesamiento del Login ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $error_msg = ''; 
    $login_exitoso = false;

    if (empty($email) || empty($password)) {
        $error_msg = "Error: Por favor, ingrese el correo y la contraseña.";
    }
    
    if (empty($error_msg)) {

        // 1. VERIFICAR ADMIN (ROOT)
        $admin_email = "root@root.com"; 
        $admin_pass  = "root"; 

        if ($email === $admin_email && $password === $admin_pass) {
            $_SESSION['loggedin'] = true;
            $_SESSION['rol'] = 'admin';
            $_SESSION['nombre_usuario'] = 'Administrador Supremo';
            header("Location: ../admin/dashboard_admin.php");
            exit();
        }
        
        // =======================================================
        // --- 2. BÚSQUEDA EN CLIENTES ---
        // =======================================================
        // ¡OJO! Agregamos 'estado' a la consulta
        $sql_cliente = "SELECT idCliente, nombre_cliente, password_cliente, estado FROM cliente WHERE email_cliente = ?";
        $stmt_cliente = $conexion->prepare($sql_cliente);
        
        if ($stmt_cliente) {
            $stmt_cliente->bind_param("s", $email);
            $stmt_cliente->execute();
            $resultado_cliente = $stmt_cliente->get_result();
            
            if ($resultado_cliente->num_rows == 1) {
                $cliente = $resultado_cliente->fetch_assoc();
                
                // --- BLOQUEO DE SEGURIDAD: Verificar estado ---
                if ($cliente['estado'] == 'inactivo') {
                    // Si está inactivo, cortamos aquí y mostramos error
                    $error_msg = "Tu cuenta ha sido desactivada. Contacta al administrador.";
                } else {
                    // Si está activo, procedemos a checar la contraseña
                    if (password_verify($password, $cliente['password_cliente']) || $password === $cliente['password_cliente']) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id_cliente'] = $cliente['idCliente'];
                        $_SESSION['nombre_cliente'] = $cliente['nombre_cliente'];
                        
                        header("Location: ../cliente/dashboard_cliente.php");
                        exit(); 
                    }
                }
                $login_exitoso = true; // Marcamos que encontramos el correo (aunque la pass falle o esté inactivo)
            }
            $stmt_cliente->close();
        }
        
        // =======================================================
        // --- 3. BÚSQUEDA EN VENDEDORES (Solo si no se encontró en clientes) ---
        // =======================================================
        
        if (empty($error_msg) && !$login_exitoso) { 
            // Agregamos 'estado' también aquí
            $sql_vendedor = "SELECT idvendedor, nombre_vendedor, password_vendedor, estado FROM vendedor WHERE email_vendedor = ?";
            $stmt_vendedor = $conexion->prepare($sql_vendedor);
            
            if ($stmt_vendedor) {
                $stmt_vendedor->bind_param("s", $email);
                $stmt_vendedor->execute();
                $resultado_vendedor = $stmt_vendedor->get_result();
                
                if ($resultado_vendedor->num_rows == 1) {
                    $vendedor = $resultado_vendedor->fetch_assoc();
                    
                    // --- BLOQUEO DE SEGURIDAD VENDEDOR ---
                    if ($vendedor['estado'] == 'inactivo') {
                        $error_msg = "Tu cuenta de vendedor ha sido desactivada.";
                    } else {
                        if (password_verify($password, $vendedor['password_vendedor']) || $password === $vendedor['password_vendedor']) {
                            $_SESSION['loggedin_vendedor'] = true;
                            $_SESSION['id_vendedor'] = $vendedor['idvendedor'];
                            $_SESSION['nombre_vendedor'] = $vendedor['nombre_vendedor'];
                            
                            header("Location: ../vendedor/dashboard_vendedor.php");
                            exit();
                        }
                    }
                }
                $stmt_vendedor->close();
            }
        }
    }

    // --- MANEJO DE ERROR FINAL ---
    if (empty($error_msg)) {
        $error_msg = "Error: El correo electrónico o la contraseña son incorrectos.";
    }
    
    $_SESSION['login_error'] = $error_msg;
    header("Location: ../../Front-end/login.php"); 
    exit();
}

$conexion->close();
?>