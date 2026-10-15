<?php
// Back-end/conexion/reenviar_otp_recuperacion.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$emailSesion = $_SESSION['email_recuperacion'] ?? '';

require_once __DIR__ . '/conexion_mongo.php';
require_once __DIR__ . '/../servicios/EmailService.php';

use Snackver\Servicios\EmailService;

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

$fase = trim($_POST['fase'] ?? '');

if ($fase === 'enviar_smtp') {
    $pendiente = $_SESSION['otp_pendiente_reenvio'] ?? null;
    unset($_SESSION['otp_pendiente_reenvio']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if (!$pendiente || empty($pendiente['email']) || empty($pendiente['codigo'])) {
        echo json_encode(['status' => 'error', 'message' => 'No hay código pendiente de reenvío.']);
        exit();
    }

    $envio = EmailService::enviarCodigoRecuperacion(
        $pendiente['email'],
        $pendiente['nombre'] ?? 'Usuario',
        $pendiente['codigo']
    );

    if ($envio['status'] === 'error') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No se pudo enviar el correo: ' . ($envio['message'] ?? 'Error desconocido')
        ]);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'Correo reenviado exitosamente.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? $_POST['email_recuperacion'] ?? $emailSesion));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    echo json_encode(['status' => 'error', 'message' => 'Correo electrónico inválido.']);
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $usuario = $colUsuarios->findOne(['email' => $email], [
        'projection' => [
            'nombre' => 1
        ]
    ]);

    if (!$usuario) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        echo json_encode(['status' => 'error', 'message' => 'No se encontró la cuenta.']);
        exit();
    }

    // Generar nuevo código OTP
    $codigoOtp = (string)random_int(100000, 999999);
    $expira = new MongoDB\BSON\UTCDateTime((time() + 900) * 1000); // 15 minutos

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

    if ($fase === 'generar') {
        $_SESSION['otp_pendiente_reenvio'] = [
            'email'  => $email,
            'nombre' => $nombreUsuario,
            'codigo' => $codigoOtp
        ];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        echo json_encode([
            'status'  => 'success',
            'message' => '¡Nuevo código enviado! Por favor revisa tu bandeja de entrada.'
        ]);
        exit();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Reenviar correo (modo directo tradicional)
    $envio = EmailService::enviarCodigoRecuperacion($email, $nombreUsuario, $codigoOtp);

    if ($envio['status'] === 'error') {
        echo json_encode([
            'status' => 'error',
            'message' => 'No se pudo enviar el correo: ' . ($envio['message'] ?? 'Error desconocido')
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'success',
        'message' => '¡Nuevo código enviado! Por favor revisa tu bandeja de entrada.'
    ]);
    exit();

} catch (Exception $e) {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    error_log("Error al reenviar OTP de recuperación: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al reenviar código: ' . $e->getMessage()]);
    exit();
}
