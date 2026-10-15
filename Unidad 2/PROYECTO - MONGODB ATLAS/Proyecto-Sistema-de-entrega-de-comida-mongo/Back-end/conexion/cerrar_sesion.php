<?php
// cerrar_sesion.php
session_start();

require_once __DIR__ . '/../servicios/HistorialService.php';
use Snackver\Servicios\HistorialService;

// Obtener datos del usuario activo para auditoría previa al cierre
$id_usuario = $_SESSION['id_usuario'] ?? $_SESSION['id_cliente'] ?? $_SESSION['id_vendedor'] ?? $_SESSION['id_admin'] ?? null;
$email = $_SESSION['email'] ?? $_SESSION['email_cliente'] ?? $_SESSION['email_vendedor'] ?? '';
$rol = $_SESSION['rol'] ?? 'usuario';

if ($id_usuario || !empty($email)) {
    HistorialService::registrar(
        $id_usuario,
        $email ?: 'usuario@snackver.me',
        $rol,
        'Cierre de sesión'
    );
}

// Eliminar todas las variables de la sesión y destruirla
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Redirigir a la página principal
header("Location: ../../index.php");
exit();