<?php
// Back-end/conexion/procesar_solicitud_recuperacion.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';
require_once __DIR__ . '/../servicios/EmailService.php';

use Snackver\Servicios\EmailService;

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' 
       || isset($_POST['ajax']) && $_POST['ajax'] === '1';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
        exit();
    }
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}

$fase = trim($_POST['fase'] ?? '');

// =========================================================================
// FASE 2 (BACKGROUND ASYNC): Despacho SMTP sin bloquear la transición UI
// =========================================================================
if ($fase === 'enviar_smtp') {
    header('Content-Type: application/json; charset=UTF-8');
    $pendiente = $_SESSION['otp_pendiente_envio'] ?? null;
    unset($_SESSION['otp_pendiente_envio']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (!$pendiente || empty($pendiente['email']) || empty($pendiente['codigo'])) {
        echo json_encode(['status' => 'error', 'message' => 'No hay código pendiente de envío.']);
        exit();
    }

    $envio = EmailService::enviarCodigoRecuperacion(
        $pendiente['email'],
        $pendiente['nombre'] ?? 'Usuario',
        $pendiente['codigo']
    );

    if ($envio['status'] === 'simulated') {
        error_log("Código OTP de recuperación simulado para {$pendiente['email']}: {$pendiente['codigo']}");
    }

    if ($envio['status'] === 'error') {
        echo json_encode([
            'status' => 'error',
            'message' => 'No pudimos enviar el correo de verificación: ' . ($envio['message'] ?? 'Error desconocido')
        ]);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Correo enviado exitosamente.']);
    exit();
}

$email = strtolower(trim($_POST['email_recuperacion'] ?? $_POST['email'] ?? ''));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = 'Por favor ingresa un correo electrónico válido.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
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

    // Buscar si el usuario existe (proyección mínima de alta velocidad)
    $usuario = $colUsuarios->findOne(['email' => $email], [
        'projection' => [
            'nombre' => 1
        ]
    ]);

    if (!$usuario) {
        $msg = 'No existe ninguna cuenta registrada con este correo electrónico.';
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        $_SESSION['resultado_cambio'] = $msg;
        header("Location: ../../Front-end/recuperacion_password.php");
        exit();
    }

    // Generar código OTP de 6 dígitos
    $codigoOtp = (string)random_int(100000, 999999);
    $expira = new MongoDB\BSON\UTCDateTime((time() + 900) * 1000); // 15 minutos

    // Guardar OTP en la colección de usuarios
    $colUsuarios->updateOne(
        ['_id' => $usuario['_id']],
        ['$set' => [
            'recuperacion_otp' => [
                'codigo' => $codigoOtp,
                'expira' => $expira,
                'creado' => new MongoDB\BSON\UTCDateTime()
            ]
        ]]
    );

    $nombreUsuario = $usuario['nombre'] ?? 'Usuario';
    $_SESSION['email_recuperacion'] = $email;

    // =========================================================================
    // FASE 1 (INSTANTÁNEA): Responder en ~0ms tras guardar en BD sin esperar SMTP
    // =========================================================================
    if ($fase === 'generar' && $isAjax) {
        $_SESSION['otp_pendiente_envio'] = [
            'email'  => $email,
            'nombre' => $nombreUsuario,
            'codigo' => $codigoOtp
        ];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status'  => 'success',
            'email'   => $email,
            'message' => 'Código de verificación generado exitosamente.'
        ]);
        exit();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Enviar correo electrónico (modo directo tradicional)
    $envio = EmailService::enviarCodigoRecuperacion($email, $nombreUsuario, $codigoOtp);

    // Si está en modo simulado por credenciales locales
    if ($envio['status'] === 'simulated') {
        error_log("Código OTP de recuperación simulado para $email: $codigoOtp");
    }

    if ($envio['status'] === 'error') {
        $msg = 'No pudimos enviar el correo de verificación: ' . ($envio['message'] ?? 'Error desconocido');
        if ($isAjax) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SESSION['resultado_cambio'] = $msg;
        session_write_close();
        header("Location: ../../Front-end/recuperacion_password.php");
        exit();
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'status' => 'success',
            'email' => $email,
            'message' => 'Código de verificación enviado exitosamente a tu correo.'
        ]);
        exit();
    }

    header("Location: ../../Front-end/recuperacion_password.php");
    exit();

} catch (Exception $e) {
    error_log("Error al procesar recuperación: " . $e->getMessage());
    $msg = 'Error al conectar con el servidor. Por favor intenta más tarde.';
    $_SESSION['resultado_cambio'] = $msg;
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    header("Location: ../../Front-end/recuperacion_password.php");
    exit();
}
