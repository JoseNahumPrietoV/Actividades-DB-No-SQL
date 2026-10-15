<?php
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

while (ob_get_level() > 0) {
    @ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$tipoSolicitado = $_POST['tipo'] ?? $_GET['tipo'] ?? null;

if ($tipoSolicitado === 'vendedor') {
    $idUsuario = $_SESSION['id_vendedor'] ?? null;
    $tipoUsuario = 'vendedor';
} elseif ($tipoSolicitado === 'cliente') {
    $idUsuario = $_SESSION['id_cliente'] ?? null;
    $tipoUsuario = 'cliente';
} else {
    if (isset($_SESSION['id_vendedor']) && !isset($_SESSION['id_cliente'])) {
        $idUsuario = $_SESSION['id_vendedor'];
        $tipoUsuario = 'vendedor';
    } elseif (isset($_SESSION['id_cliente'])) {
        $idUsuario = $_SESSION['id_cliente'];
        $tipoUsuario = 'cliente';
    } else {
        $idUsuario = $_SESSION['id_vendedor'] ?? null;
        $tipoUsuario = $idUsuario ? 'vendedor' : null;
    }
}

if (!$idUsuario) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No has iniciado sesión.'
    ]);
    exit;
}

session_write_close();

require_once dirname(__DIR__) . '/servicios/NotificacionService.php';

use Snackver\Servicios\NotificacionService;

$exito = NotificacionService::marcarTodasLeidas($idUsuario, $tipoUsuario);

if ($exito) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Todas las notificaciones se marcaron como leídas.'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'No se pudieron actualizar las notificaciones.'
    ]);
}
exit;
