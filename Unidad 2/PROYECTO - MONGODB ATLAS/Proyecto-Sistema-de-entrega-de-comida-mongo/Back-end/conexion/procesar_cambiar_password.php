<?php
// Back-end/conexion/procesar_cambiar_password.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' 
       || isset($_POST['ajax']) && $_POST['ajax'] === '1';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
        exit();
    }
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

$email = strtolower(trim($_POST['email_recuperacion'] ?? $_POST['email'] ?? ''));
$pass1 = trim($_POST['nueva_password'] ?? '');
$pass2 = trim($_POST['confirm_password'] ?? '');
$token = trim($_POST['recuperacion_token'] ?? '');

// Verificar autorización de sesión previa por OTP
$emailAutorizado = $_SESSION['recuperacion_email_autorizado'] ?? null;
$tokenAutorizado = $_SESSION['recuperacion_token'] ?? null;

if (empty($emailAutorizado) || $emailAutorizado !== $email) {
    $msg = 'Sesión no autorizada o expirada. Por favor solicita y confirma un código de verificación nuevamente.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

if (!empty($tokenAutorizado) && !empty($token) && $tokenAutorizado !== $token) {
    $msg = 'Token de seguridad inválido. Por favor intenta el proceso nuevamente.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

if (empty($pass1) || empty($pass2)) {
    $msg = 'Todos los campos son obligatorios.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

if ($pass1 !== $pass2) {
    $msg = 'Las contraseñas no coinciden.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

if (strlen($pass1) < 6) {
    $msg = 'La contraseña debe tener al menos 6 caracteres.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $usuario = $colUsuarios->findOne(['email' => $email], [
        'projection' => [
            '_id' => 1,
            'rol' => 1
        ]
    ]);

    if (!$usuario) {
        $msg = 'No se encontró ninguna cuenta asociada a este correo electrónico.';
        if ($isAjax) {
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        $_SESSION['resultado_cambio'] = $msg;
        header("Location: ../../Front-end/recuperacion_password.php");
        exit();
    }

    $pass_hash = password_hash($pass1, PASSWORD_BCRYPT);

    $colUsuarios->updateOne(
        ['email' => $email],
        [
            '$set' => [
                'password_hash' => $pass_hash,
                'fecha_actualizacion' => new MongoDB\BSON\UTCDateTime()
            ],
            '$unset' => [
                'recuperacion_otp' => ""
            ]
        ]
    );

    // Limpiar variables de sesión de recuperación
    unset($_SESSION['recuperacion_email_autorizado']);
    unset($_SESSION['recuperacion_token']);
    unset($_SESSION['email_recuperacion']);

    $origen = trim($_POST['origen'] ?? '');
    $esZonaSeguridad = ($origen === 'seguridad') || !empty($_SESSION['id_cliente']) || !empty($_SESSION['id_vendedor']);

    if ($esZonaSeguridad) {
        // Registrar en auditoría de historial
        try {
            require_once __DIR__ . '/../servicios/HistorialService.php';
            \Snackver\Servicios\HistorialService::registrar(
                $usuario['_id'],
                $email,
                $usuario['rol'] ?? ($_SESSION['rol'] ?? 'usuario'),
                'Cambio de contraseña',
                ['origen' => 'Zona de Seguridad']
            );
        } catch (\Throwable $th) {
            error_log("Error auditando cambio de contraseña: " . $th->getMessage());
        }

        // Mantener sesión activa y definir retorno a Mi Perfil o Mi Tienda
        $esVendedor = !empty($_SESSION['id_vendedor']) || ($usuario['rol'] ?? '') === 'vendedor' || ($_SESSION['rol'] ?? '') === 'vendedor';
        $redirectUrl = $esVendedor ? '../Back-end/vendedor/MiTienda.php' : '../Back-end/cliente/Miperfil.php';

        $_SESSION['mensaje_perfil'] = "¡Contraseña actualizada con éxito!";

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if ($isAjax) {
            echo json_encode([
                'status' => 'success',
                'message' => '¡Contraseña actualizada con éxito! Redirigiendo...',
                'redirect' => $redirectUrl
            ]);
            exit();
        }

        header("Location: " . $redirectUrl);
        exit();
    }

    $_SESSION['login_success'] = "¡Contraseña actualizada con éxito! Por favor inicia sesión con tu nueva contraseña.";

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if ($isAjax) {
        echo json_encode([
            'status' => 'success',
            'message' => '¡Contraseña restablecida con éxito! Redirigiendo...',
            'redirect' => 'login.php'
        ]);
        exit();
    }

    header("Location: ../../Front-end/login.php");
    exit();

} catch (Exception $e) {
    error_log("Error al restablecer contraseña: " . $e->getMessage());
    $msg = 'Error al conectar con el servidor. Por favor intente más tarde.';
    if ($isAjax) {
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['resultado_cambio'] = $msg;
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}