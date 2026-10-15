<?php
// Back-end/conexion/procesar_registro.php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

require_once __DIR__ . '/conexion_mongo.php';
require_once dirname(__DIR__) . '/servicios/EmailService.php';
require_once dirname(__DIR__) . '/servicios/HistorialService.php';

use Snackver\Servicios\EmailService;
use Snackver\Servicios\HistorialService;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $isAjax = (!empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'));
    $tipo_usuario = $_POST['user_type'] ?? 'cliente';

    $responderError = function($msg) use ($isAjax) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => $msg]);
            exit();
        }
        die("Error: " . $msg);
    };

    if ($tipo_usuario === 'cliente') {
        $nombre   = trim($_POST['nombre_cliente'] ?? '');
        $apellido = trim($_POST['apellido_cliente'] ?? '');
        $telefono = trim($_POST['telefono_cliente'] ?? '');
        $email    = strtolower(trim($_POST['email_cliente'] ?? ''));
        $pass     = trim($_POST['password_cliente'] ?? '');
        $confirm  = trim($_POST['confirm_password_cliente'] ?? '');
        $tienda   = null;

    } elseif ($tipo_usuario === 'vendedor') {
        $nombre         = trim($_POST['nombre_vendedor'] ?? '');
        $apellido       = trim($_POST['apellido_vendedor'] ?? '');
        $telefono       = trim($_POST['telefono_vendedor'] ?? '');
        $nombre_negocio = trim($_POST['nombre_negocio'] ?? '');
        $descripcion    = trim($_POST['descripcion'] ?? '');
        $direccion      = trim($_POST['dirección'] ?? '');
        $email          = strtolower(trim($_POST['email_vendedor'] ?? ''));
        $pass           = trim($_POST['password_vendedor'] ?? '');
        $confirm        = trim($_POST['confirm_password_vendedor'] ?? '');

        if (empty($nombre_negocio) || empty($descripcion) || empty($direccion)) {
            $responderError("Faltan campos obligatorios del negocio (nombre del negocio, descripción o dirección).");
        }

        $tienda = [
            'nombre_negocio' => $nombre_negocio,
            'descripcion'    => $descripcion,
            'direccion'      => $direccion
        ];
    } else {
        $responderError("Tipo de usuario no válido.");
    }

    // 1. Validaciones básicas
    if (empty($nombre) || empty($apellido) || empty($email) || empty($pass)) {
        $responderError("Todos los campos obligatorios deben completarse.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $responderError("El correo electrónico no tiene un formato válido.");
    }

    if ($pass !== $confirm) {
        $responderError("Las contraseñas no coinciden. Por favor verifica ambos campos.");
    }

    if (strlen($pass) < 6) {
        $responderError("La contraseña debe tener al menos 6 caracteres.");
    }

    // 2. Seguridad: Hashing seguro con bcrypt
    $password_cifrada = password_hash($pass, PASSWORD_BCRYPT);

    // 3. Generar código OTP de 6 dígitos con expiración de 15 minutos
    $codigoOtp = (string)random_int(100000, 999999);
    $expiraOtp = new MongoDB\BSON\UTCDateTime((time() + (15 * 60)) * 1000);

    // 4. Inserción 100% en MongoDB Atlas (Colección: usuarios)
    try {
        $mongoDb = MongoConexion::getConexion();
        $colUsuarios = $mongoDb->selectCollection('usuarios');

        // Verificar si el correo ya existe
        $existe = $colUsuarios->findOne(['email' => $email], [
            'projection' => [
                'estado' => 1
            ]
        ]);
        if ($existe) {
            $estadoUsuario = $existe['estado'] ?? 'activo';
            if ($estadoUsuario === 'pendiente_verificacion') {
                $responderError("Este correo ya está registrado pero su cuenta está pendiente de verificación. Inicia sesión con tus credenciales para recibir un código nuevo y activar tu cuenta.");
            } else {
                $responderError("Ya existe una cuenta registrada con el correo: $email");
            }
        }

        $docUsuario = [
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'telefono'         => $telefono,
            'email'            => $email,
            'password_hash'    => $password_cifrada,
            'rol'              => $tipo_usuario,
            'estado'           => 'pendiente_verificacion',
            'verificacion_otp' => [
                'codigo' => $codigoOtp,
                'expira' => $expiraOtp
            ],
            'fecharegistro'    => new MongoDB\BSON\UTCDateTime()
        ];

        if ($tienda !== null) {
            $docUsuario['tienda'] = $tienda;
        }

        $resInsert = $colUsuarios->insertOne($docUsuario);
        $idNuevoUsuario = $resInsert->getInsertedId();

        // Registrar auditoría de nuevo registro
        $detallesReg = [
            'nombre_completo' => trim("$nombre $apellido"),
            'telefono'        => $telefono
        ];
        if ($tienda !== null) {
            $detallesReg['negocio'] = $tienda['nombre_negocio'] ?? '';
        }

        HistorialService::registrar(
            $idNuevoUsuario,
            $email,
            $tipo_usuario,
            'Registro de nueva cuenta',
            $detallesReg
        );

    } catch (Exception $e) {
        error_log("Error al guardar usuario en base de datos: " . $e->getMessage());
        $responderError("No se pudo registrar el usuario. Por favor intente más tarde. Detalle: " . $e->getMessage());
    }

    // 5. Guardar sesión y liberar candado inmediatamente antes del envío SMTP para evitar bloqueos
    $_SESSION['email_verificacion'] = $email;
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Envío del Correo OTP vía Gmail SMTP / PHPMailer
    $envio = EmailService::enviarCodigoVerificacion($email, $nombre, $codigoOtp);

    if (!$isAjax && ($envio['status'] === 'simulated' || $envio['status'] === 'error')) {
        @session_start();
        if ($envio['status'] === 'simulated') {
            $_SESSION['success_otp'] = "Código generado (modo demo): " . $codigoOtp;
        } elseif ($envio['status'] === 'error') {
            $_SESSION['error_otp'] = "No se pudo enviar el código al correo: " . ($envio['message'] ?? 'Error de conexión');
        }
        session_write_close();
    }

    // Si es AJAX, responder con JSON para abrir el modal flotante
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'email'  => $email,
            'nombre' => $nombre,
            'message' => ($envio['status'] === 'error')
                ? 'Cuenta creada, pero hubo un problema al enviar el correo. Puedes dar clic en "Reenviar código" en la siguiente pantalla.'
                : 'Código de verificación enviado exitosamente.'
        ]);
        exit();
    }

    // 6. Redirigir a la pantalla de verificación OTP tradicional
    header("Location: ../../Front-end/verificar_codigo.php?email=" . urlencode($email));
    exit();
}

ob_end_flush();
?>
