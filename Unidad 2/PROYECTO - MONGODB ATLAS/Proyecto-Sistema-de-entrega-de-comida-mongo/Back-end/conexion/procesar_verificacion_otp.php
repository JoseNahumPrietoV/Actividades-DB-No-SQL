<?php
// Back-end/conexion/procesar_verificacion_otp.php
session_start();

require_once __DIR__ . '/conexion_mongo.php';
require_once dirname(__DIR__) . '/servicios/HistorialService.php';

use Snackver\Servicios\HistorialService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../../Front-end/login.php");
    exit();
}

$email = strtolower(trim($_POST['email'] ?? ''));
$codigoIngresado = trim($_POST['codigo_otp'] ?? '');

$isAjax = (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));

$responderErrorOtp = function($msg, $email) use ($isAjax) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $msg]);
        exit();
    }
    $_SESSION['error_otp'] = $msg;
    if (!empty($email)) {
        $_SESSION['abrir_modal_otp'] = $email;
    }
    header("Location: ../../Front-end/login.php");
    exit();
};

if (empty($email) || empty($codigoIngresado)) {
    $responderErrorOtp("Por favor ingresa el código de 6 dígitos.", $email);
}

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    // Buscar al usuario por correo
    $usuario = $colUsuarios->findOne(['email' => $email]);

    if (!$usuario) {
        $responderErrorOtp("No se encontró ninguna cuenta asociada a este correo.", $email);
    }

    // Verificar si ya está activo
    if (($usuario['estado'] ?? '') === 'activo') {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'redirect' => 'login.php', 'message' => 'Tu cuenta ya está activa. Por favor inicia sesión.']);
            exit();
        }
        $_SESSION['login_success'] = "Tu cuenta ya está activa. Por favor inicia sesión.";
        header("Location: ../../Front-end/login.php");
        exit();
    }

    $otpData = $usuario['verificacion_otp'] ?? null;

    if (!$otpData || !isset($otpData['codigo'])) {
        $responderErrorOtp("No hay ningún código pendiente. Por favor solicita uno nuevo.", $email);
    }

    // Validar expiración (UTC timestamp)
    $expiraTimestamp = 0;
    if ($otpData['expira'] instanceof MongoDB\BSON\UTCDateTime) {
        $expiraTimestamp = $otpData['expira']->toDateTime()->getTimestamp();
    } elseif (is_numeric($otpData['expira'])) {
        $expiraTimestamp = (int)$otpData['expira'];
    }

    if (time() > $expiraTimestamp) {
        $responderErrorOtp("El código ha expirado (más de 15 minutos). Por favor solicita uno nuevo.", $email);
    }

    // Comparar códigos
    if ((string)$otpData['codigo'] !== (string)$codigoIngresado) {
        $responderErrorOtp("El código ingresado es incorrecto. Inténtalo de nuevo.", $email);
    }

    // Código válido -> Activar cuenta en MongoDB
    $colUsuarios->updateOne(
        ['_id' => $usuario['_id']],
        [
            '$set' => ['estado' => 'activo'],
            '$unset' => ['verificacion_otp' => ""]
        ]
    );

    // Iniciar sesión automáticamente
    $_SESSION['loggedin'] = true;
    $rol = $usuario['rol'] ?? 'cliente';
    $_SESSION['rol'] = $rol;
    $_SESSION['id_usuario'] = (string)$usuario['_id'];
    $_SESSION['email'] = $usuario['email'] ?? $email;

    // Registrar auditoría de 2FA completada
    HistorialService::registrar(
        $usuario['_id'],
        $usuario['email'] ?? $email,
        $rol,
        'Verificación 2FA completada',
        ['metodo' => 'Código OTP 6 dígitos']
    );

    if ($rol === 'cliente') {
        $nombreC = trim($usuario['nombre'] ?? '');
        $apellidoC = trim($usuario['apellido'] ?? '');
        $_SESSION['id_cliente'] = (string)$usuario['_id'];
        $_SESSION['nombre_cliente'] = $nombreC;
        $_SESSION['apellido_cliente'] = $apellidoC;
        $_SESSION['email_cliente'] = $usuario['email'] ?? '';
        $_SESSION['mensaje_perfil'] = "¡Cuenta verificada con éxito! Bienvenido a Snackver.";
        $_SESSION['datos_cliente'] = [
            'nombre_cliente'   => $nombreC,
            'apellido_cliente' => $apellidoC,
            'nombre_completo'  => trim("$nombreC $apellidoC") ?: 'Cliente Universitario',
            'rol_label'        => 'Estudiante - Cliente',
            'telefono_cliente' => $usuario['telefono'] ?? '',
            'email_cliente'    => $usuario['email'] ?? ''
        ];
        $destino = '../cliente/dashboard_cliente.php';
    } elseif ($rol === 'vendedor') {
        $tienda = $usuario['tienda'] ?? [];
        $nombreV = trim($usuario['nombre'] ?? '');
        $apellidoV = trim($usuario['apellido'] ?? '');
        $_SESSION['loggedin_vendedor'] = true;
        $_SESSION['id_vendedor'] = (string)$usuario['_id'];
        $_SESSION['nombre_vendedor'] = $nombreV;
        $_SESSION['apellido_vendedor'] = $apellidoV;
        $_SESSION['email_vendedor'] = $usuario['email'] ?? '';
        $_SESSION['mensaje_perfil'] = "¡Cuenta de vendedor verificada con éxito!";
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
    } else {
        $destino = '../../Front-end/login.php';
    }

    // Liberar candado de sesión inmediatamente
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    if ($isAjax) {
        $destinoFrontend = ($rol === 'admin') 
            ? '../Back-end/admin/dashboard_admin.php' 
            : (($rol === 'vendedor') 
                ? '../Back-end/vendedor/dashboard_vendedor.php' 
                : '../Back-end/cliente/dashboard_cliente.php');

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'redirect' => $destinoFrontend,
            'message' => '¡Cuenta verificada con éxito! Redirigiendo a tu panel...'
        ]);
        exit();
    }

    header("Location: $destino");
    exit();

} catch (Exception $e) {
    error_log("Error en verificación OTP: " . $e->getMessage());
    $responderErrorOtp("Error al procesar la verificación: " . $e->getMessage(), $email);
}
