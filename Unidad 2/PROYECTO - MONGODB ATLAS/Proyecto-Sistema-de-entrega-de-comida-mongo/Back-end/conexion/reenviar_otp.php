<?php
// Back-end/conexion/reenviar_otp.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';
require_once dirname(__DIR__) . '/servicios/EmailService.php';

use Snackver\Servicios\EmailService;

$isAjax = (!empty($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

$email = strtolower(trim($_REQUEST['email'] ?? $_SESSION['email_verificacion'] ?? ''));

// Liberar candado de sesión inmediatamente tras obtener los datos necesarios
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$responder = function($status, $msg, $email = '') use ($isAjax) {
    if (!$isAjax) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        if ($status === 'success') {
            $_SESSION['success_otp'] = $msg;
        } else {
            $_SESSION['error_otp'] = $msg;
        }
        if (!empty($email)) {
            $_SESSION['abrir_modal_otp'] = $email;
        }
        session_write_close();
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => $status, 'message' => $msg]);
        exit();
    }
    header("Location: ../../Front-end/login.php");
    exit();
};

if (empty($email)) {
    $responder('error', "No se proporcionó un correo para reenviar el código.");
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $usuario = $colUsuarios->findOne(['email' => $email], [
        'projection' => [
            'estado' => 1,
            'nombre' => 1
        ]
    ]);

    if (!$usuario) {
        $responder('error', "No se encontró cuenta para ese correo.", $email);
    }

    if (($usuario['estado'] ?? '') === 'activo') {
        $responder('error', "Tu cuenta ya está activa. Puedes iniciar sesión.", $email);
    }

    // Generar nuevo código de 6 dígitos
    $nuevoCodigo = (string)random_int(100000, 999999);
    $expira = new MongoDB\BSON\UTCDateTime((time() + (15 * 60)) * 1000); // 15 minutos

    $colUsuarios->updateOne(
        ['_id' => $usuario['_id']],
        ['$set' => [
            'verificacion_otp' => [
                'codigo' => $nuevoCodigo,
                'expira' => $expira
            ]
        ]]
    );

    // Enviar correo
    $envio = EmailService::enviarCodigoVerificacion($email, $usuario['nombre'], $nuevoCodigo);

    if ($envio['status'] === 'success') {
        $responder('success', "¡Nuevo código enviado! Revisa tu correo.", $email);
    } elseif ($envio['status'] === 'simulated') {
        $responder('success', "Código generado (modo demo): " . $nuevoCodigo, $email);
    } else {
        $responder('error', "No se pudo enviar el correo: " . $envio['message'], $email);
    }

} catch (Exception $e) {
    $responder('error', "Error al reenviar código: " . $e->getMessage(), $email);
}

