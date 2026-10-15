<?php
// Back-end/conexion/procesar_login.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';
require_once dirname(__DIR__) . '/servicios/EmailService.php';
require_once dirname(__DIR__) . '/servicios/HistorialService.php';

use Snackver\Servicios\EmailService;
use Snackver\Servicios\HistorialService;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $isAjax = (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    $responderError = function($msg) use ($isAjax) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        $_SESSION['login_error'] = $msg;
        header("Location: ../../Front-end/login.php");
        exit();
    };

    if (empty($email) || empty($password)) {
        $responderError("Por favor ingrese su correo y contraseña.");
    }

    // AUTENTICACIÓN 100% EN MONGODB ATLAS (Colección: usuarios)
    try {
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');
        $usuario = $colUsuarios->findOne(['email' => $email]);

        if (!$usuario) {
            $responderError("No se encontró ninguna cuenta asociada a este correo electrónico.");
        }

        $hash   = $usuario['password_hash'] ?? '';
        $estado = $usuario['estado'] ?? 'activo';

        // 1. Validar contraseña primero (para seguridad estricta)
        if (!password_verify($password, $hash) && $password !== $hash) {
            $responderError("Contraseña incorrecta.");
        }

        // 2. Si la contraseña es correcta, verificar si la cuenta está inactiva
        if ($estado === 'inactivo') {
            $responderError("Tu cuenta ha sido desactivada. Contacta al administrador.");
        }

        // 3. Si la cuenta está en pausa (pendiente_verificacion)
        if ($estado === 'pendiente_verificacion') {
            // SIEMPRE generar un código OTP nuevo y diferente en cada reintento
            $nuevoCodigo = (string)random_int(100000, 999999);
            $nuevaExpira = new MongoDB\BSON\UTCDateTime((time() + (15 * 60)) * 1000);

            $colUsuarios->updateOne(
                ['_id' => $usuario['_id']],
                ['$set' => [
                    'verificacion_otp' => [
                        'codigo' => $nuevoCodigo,
                        'expira' => $nuevaExpira
                    ]
                ]]
            );

            $_SESSION['email_verificacion'] = $email;
            if (!$isAjax) {
                $_SESSION['abrir_modal_otp'] = $email;
            }
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }

            $envio = EmailService::enviarCodigoVerificacion($email, $usuario['nombre'] ?? 'Usuario', $nuevoCodigo);

            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'status'  => 'unverified',
                    'email'   => $email,
                    'nombre'  => $usuario['nombre'] ?? '',
                    'message' => ($envio['status'] === 'error')
                        ? 'Tu cuenta requiere verificación previa. No pudimos enviar el correo (' . ($envio['message'] ?? 'error') . '). Haz clic en "Reenviar código" para intentar de nuevo.'
                        : 'Hemos enviado un nuevo código de verificación a tu correo.'
                ]);
                exit();
            }

            // Fallback si no es AJAX: permanecer en login.php y abrir modal directamente
            header("Location: ../../Front-end/login.php");
            exit();
        }

        // 4. Si la cuenta está activa, iniciar sesión normalmente
        $_SESSION['loggedin'] = true;
        $rol = $usuario['rol'] ?? 'cliente';
        $_SESSION['rol'] = $rol;
        $_SESSION['id_usuario'] = (string)$usuario['_id'];
        $_SESSION['email'] = $usuario['email'] ?? $email;

        if ($rol === 'admin') {
            $_SESSION['id_admin'] = (string)$usuario['_id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre'] ?? 'Administrador';
            $destino = '../admin/dashboard_admin.php';
            $destinoFrontend = '../Back-end/admin/dashboard_admin.php';
        } elseif ($rol === 'vendedor') {
            $_SESSION['loggedin_vendedor'] = true;
            $_SESSION['id_vendedor'] = (string)$usuario['_id'];
            $_SESSION['nombre_vendedor'] = $usuario['nombre'];
            $_SESSION['apellido_vendedor'] = $usuario['apellido'] ?? '';
            $tienda = $usuario['tienda'] ?? [];
            $nombreV = trim($usuario['nombre'] ?? '');
            $apellidoV = trim($usuario['apellido'] ?? '');
            $_SESSION['datos_vendedor'] = [
                'nombre_vendedor'          => $nombreV,
                'apellido_vendedor'        => $apellidoV,
                'nombre_completo'          => trim("$nombreV $apellidoV") ?: 'Vendedor',
                'rol_label'                => 'Vendedor',
                'telefono_vendedor'        => $usuario['telefono'] ?? '',
                'email_vendedor'           => $usuario['email'] ?? '',
                'nombre_negocio'           => $tienda['nombre_negocio'] ?? '',
                'descripcion'              => $tienda['descripcion'] ?? '',
                'dirección'                => $tienda['direccion'] ?? ($tienda['dirección'] ?? ''),
                'foto_negocio'             => $tienda['foto_negocio'] ?? '',
                'fechaRegistro'            => isset($usuario['fecharegistro']) ? date('Y-m-d H:i:s', $usuario['fecharegistro']->toDateTime()->getTimestamp()) : date('Y-m-d H:i:s')
            ];
            $destino = '../vendedor/dashboard_vendedor.php';
            $destinoFrontend = '../Back-end/vendedor/dashboard_vendedor.php';
        } else {
            $_SESSION['id_cliente'] = (string)$usuario['_id'];
            $_SESSION['nombre_cliente'] = $usuario['nombre'];
            $_SESSION['apellido_cliente'] = $usuario['apellido'] ?? '';
            $_SESSION['email_cliente'] = $usuario['email'];
            $nombreC = trim($usuario['nombre'] ?? '');
            $apellidoC = trim($usuario['apellido'] ?? '');
            $_SESSION['datos_cliente'] = [
                'nombre_cliente'   => $nombreC,
                'apellido_cliente' => $apellidoC,
                'nombre_completo'  => trim("$nombreC $apellidoC") ?: 'Cliente Universitario',
                'rol_label'        => 'Estudiante - Cliente',
                'telefono_cliente' => $usuario['telefono'] ?? '',
                'email_cliente'    => $usuario['email'] ?? ''
            ];
            $destino = '../cliente/dashboard_cliente.php';
            $destinoFrontend = '../Back-end/cliente/dashboard_cliente.php';
        }

        // Registrar auditoría de inicio de sesión exitoso
        HistorialService::registrar(
            $usuario['_id'],
            $usuario['email'] ?? $email,
            $rol,
            'Inicio de sesión exitoso',
            ['metodo' => $isAjax ? 'AJAX' : 'POST']
        );

        // Liberar candado de sesión inmediatamente para permitir navegación ultra fluida
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'   => 'success',
                'redirect' => $destinoFrontend,
                'message'  => '¡Inicio de sesión exitoso!'
            ]);
            exit();
        }

        header("Location: $destino");
        exit();

    } catch (Exception $e) {
        error_log("Error conectando a la base de datos en login: " . $e->getMessage());
        $responderError("Error de conexión con el servidor. Por favor intente más tarde.");
    }
}
?>