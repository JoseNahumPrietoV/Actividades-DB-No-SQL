<?php
// --- 1. INICIAR LA SESIÓN Y CONFIGURACIÓN ---
session_start();

// Configuración de errores para desarrollo (mantener en el desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

// --- 2. Configuración de la Base de Datos ---
$servidor = "127.0.0.1";
$usuario = "root";
$contrasena = "2884";
$base_de_datos = "sistemawebsnackver";

// --- 3. Crear la Conexión ---
$conexion = new mysqli($servidor, $usuario, $contrasena, $base_de_datos);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- 4. Procesar el Formulario ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Asegura que el tipo de usuario exista
    $tipo_usuario = isset($_POST['user_type']) ? $_POST['user_type'] : '';

    if ($tipo_usuario == 'cliente') {

        // --- Bloque Cliente (ya corregido) ---

        // Asume que el input HTML tiene name="confirm_password_cliente"
        $confirm_password_texto_plano = trim($_POST['confirm_password_cliente'] ?? '');

        // 1. Recibir datos del formulario
        $nombre_cliente = trim($_POST['nombre_cliente']);
        $apellido_cliente = trim($_POST['apellido_cliente']);
        $telefono_cliente = trim($_POST['telefono_cliente']);
        $email_cliente = trim($_POST['email_cliente']);

        // 🔑 Contraseña principal (Para hashear)
        $password_ingresada = trim($_POST['password_cliente']);

        // 2. Validar que las contraseñas coincidan
        if ($password_ingresada !== $confirm_password_texto_plano) {
            die("Error: Las contraseñas no coinciden. Por favor, asegúrate de que ambos campos de contraseña son iguales.");
        }

        // 3. Cifrar la contraseña principal
        $password_cifrada = password_hash($password_ingresada, PASSWORD_DEFAULT);

        // 4. SQL: INCLUYE el campo confirm_password_cliente y usa 6 placeholders '?'
        $sql = "INSERT INTO cliente (nombre_cliente, apellido_cliente, telefono_cliente, email_cliente, password_cliente, confirm_password_cliente, fechaRegistro, estado) VALUES (?, ?, ?, ?, ?, ?, NOW(), 'activo')";
        $stmt = $conexion->prepare($sql);

        if ($stmt) {
            // 5. bind_param: Usa "ssssss" (6 strings)
            $stmt->bind_param(
                "ssssss",
                $nombre_cliente,
                $apellido_cliente,
                $telefono_cliente,
                $email_cliente,
                $password_cifrada,
                $confirm_password_texto_plano // Se inserta el valor en texto plano
            );

            if ($stmt->execute()) {
                $id_nuevo_cliente = $stmt->insert_id;
                $_SESSION['id_cliente'] = $id_nuevo_cliente;
                $_SESSION['nombre_cliente'] = $nombre_cliente;
                $_SESSION['loggedin'] = true;

                header("Location: ../cliente/dashboard_cliente.php");
                exit();
            } else {
                echo "Error al registrar el cliente: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error al preparar la consulta: " . $conexion->error;
        }
    } elseif ($tipo_usuario == 'vendedor') {

        // --- Bloque Vendedor Corregido ---

        // **IMPORTANTE: Debes asegurarte que el input HTML del vendedor tenga name="confirm_password_vendedor"**
        $confirm_password_texto_plano_vendedor = trim($_POST['confirm_password_vendedor'] ?? '');

        // 1. Recibir datos del formulario
        $nombre_vendedor = trim($_POST['nombre_vendedor']);
        $apellido_vendedor = trim($_POST['apellido_vendedor']);
        $telefono_vendedor = trim($_POST['telefono_vendedor']);
        $nombre_negocio = trim($_POST['nombre_negocio']);
        $email_vendedor = trim($_POST['email_vendedor']);

        // CAMPOS AGREGADOS:
        $descripcion = trim($_POST['descripcion'] ?? ''); // Asume name="descripcion" en el form
        $dirección = trim($_POST['dirección'] ?? '');     // Asume name="dirección" en el form

        // 🔑 Contraseña principal (Para hashear)
        $password_ingresada = trim($_POST['password_vendedor']);

        // 2. Validar que las contraseñas coincidan
        if ($password_ingresada !== $confirm_password_texto_plano_vendedor) {
            die("Error: Las contraseñas no coinciden. Por favor, asegúrate de que ambos campos de contraseña son iguales.");
        }
        // 2.5. Validación básica de campos requeridos
        if (empty($nombre_vendedor) || empty($apellido_vendedor) || empty($email_vendedor) || empty($password_ingresada) || empty($descripcion) || empty($dirección)) {
            die("Error: Faltan campos obligatorios. Asegúrate de llenar nombre, apellido, email, contraseña, descripción y dirección.");
        }


        // 3. Cifrar la contraseña
        $password_cifrada = password_hash($password_ingresada, PASSWORD_DEFAULT);

        // 4. SQL: INCLUYE 'descripcion' y 'dirección'. Ahora son 9 placeholders '?'
        $sql = "INSERT INTO vendedor (nombre_vendedor, apellido_vendedor, telefono_vendedor, email_vendedor, password_vendedor, confirm_password_vendedor, nombre_negocio, descripcion, dirección, fechaRegistro, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'activo')";
        $stmt = $conexion->prepare($sql);

        if ($stmt) {
            // 5. bind_param: Usa "sssssssss" (9 strings)
            // 1-nombre, 2-apellido, 3-telefono, 4-email, 5-pass_cifrada, 6-confirm_pass, 7-nombre_negocio, 8-descripcion, 9-dirección
            $stmt->bind_param(
                "sssssssss",
                $nombre_vendedor,
                $apellido_vendedor,
                $telefono_vendedor,
                $email_vendedor,
                $password_cifrada,
                $confirm_password_texto_plano_vendedor,
                $nombre_negocio,
                $descripcion,
                $dirección
            );

            if ($stmt->execute()) {
                $id_nuevo_vendedor = $stmt->insert_id;
                $_SESSION['id_vendedor'] = $id_nuevo_vendedor;
                $_SESSION['nombre_vendedor'] = $nombre_vendedor;
                $_SESSION['loggedin_vendedor'] = true;

                header("Location: ../vendedor/dashboard_vendedor.php");
                exit();
            } else {
                echo "Error al registrar el vendedor: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Error al preparar la consulta: " . $conexion->error;
        }
    }
}

$conexion->close();
ob_end_flush();
