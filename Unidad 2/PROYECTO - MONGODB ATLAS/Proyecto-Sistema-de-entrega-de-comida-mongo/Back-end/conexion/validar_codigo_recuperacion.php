<?php
// Back-end/conexion/validar_codigo_recuperacion.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$codigoIngresado = trim($_POST['codigo_otp'] ?? '');

if (empty($email) || empty($codigoIngresado)) {
    echo json_encode(['status' => 'error', 'message' => 'Por favor ingresa los 6 dígitos del código.']);
    exit();
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $usuario = $colUsuarios->findOne(['email' => $email], [
        'projection' => [
            'recuperacion_otp' => 1
        ]
    ]);

    if (!$usuario) {
        echo json_encode(['status' => 'error', 'message' => 'No se encontró ninguna cuenta asociada a este correo.']);
        exit();
    }

    $otpData = $usuario['recuperacion_otp'] ?? null;

    if (!$otpData || !isset($otpData['codigo'])) {
        echo json_encode(['status' => 'error', 'message' => 'No hay ningún código activo. Solicita uno nuevo.']);
        exit();
    }

    // Verificar si el código ha expirado
    if (isset($otpData['expira'])) {
        $expiraTime = ($otpData['expira'] instanceof MongoDB\BSON\UTCDateTime)
            ? (int)($otpData['expira']->toDateTime()->getTimestamp())
            : (int)$otpData['expira'];

        if (time() > $expiraTime) {
            echo json_encode(['status' => 'error', 'message' => 'El código de verificación ha expirado. Solicita uno nuevo.']);
            exit();
        }
    }

    // Verificar código
    if ((string)$otpData['codigo'] !== (string)$codigoIngresado) {
        echo json_encode(['status' => 'error', 'message' => 'El código ingresado es incorrecto. Inténtalo de nuevo.']);
        exit();
    }

    // Código válido -> Autorizar sesión temporal para el cambio de contraseña
    $_SESSION['recuperacion_email_autorizado'] = $email;
    $token = bin2hex(random_bytes(16));
    $_SESSION['recuperacion_token'] = $token;

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    echo json_encode([
        'status' => 'success',
        'email' => $email,
        'token' => $token,
        'message' => '¡Código confirmado correctamente!'
    ]);
    exit();

} catch (Exception $e) {
    error_log("Error al validar código de recuperación: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error al validar el código: ' . $e->getMessage()]);
    exit();
}
