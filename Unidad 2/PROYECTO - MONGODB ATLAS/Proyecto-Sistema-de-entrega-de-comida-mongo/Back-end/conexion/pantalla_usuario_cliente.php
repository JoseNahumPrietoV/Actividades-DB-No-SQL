<?php
// Back-end/conexion/pantalla_usuario_cliente.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/conexion_mongo.php';

// 1. Verificar sesión
if (!isset($_SESSION['id_cliente'])) {
    header('Location: ../../Front-end/login.php');
    exit();
}

$idClienteLogueado = $_SESSION['id_cliente'];
$datos_cliente = [];

// Micro-caché de sesión: Si ya tenemos los datos cargados en sesión, usarlos al instante sin round-trip de red
if (!empty($_SESSION['datos_cliente']) && is_array($_SESSION['datos_cliente'])) {
    $datos_cliente = $_SESSION['datos_cliente'];
    try {
        /** @var \MongoDB\Database $mongoDb */
        $mongoDb = MongoConexion::getConexion();
        /** @var \MongoDB\Database $conn */
        $conn = $mongoDb;
    } catch (\Throwable $e) {
        // Conexión perezosa si se necesita más adelante
    }
} else {
    try {
        /** @var \MongoDB\Database $mongoDb */
        $mongoDb = MongoConexion::getConexion();
        /** @var \MongoDB\Database $conn */
        $conn = $mongoDb;
        $colUsuarios = $mongoDb->selectCollection('usuarios');

        $filtro = [];
        if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
            $filtro = ['_id' => new MongoDB\BSON\ObjectId($idClienteLogueado)];
        } else {
            $filtro = ['$or' => [
                ['_id' => (int)$idClienteLogueado],
                ['_id' => (string)$idClienteLogueado],
                ['email' => $_SESSION['email_cliente'] ?? '']
            ]];
        }

        $usuario = $colUsuarios->findOne($filtro, [
            'projection' => [
                'nombre'   => 1,
                'apellido' => 1,
                'telefono' => 1,
                'email'    => 1
            ]
        ]);

        if ($usuario) {
            $nombreCliente = trim($usuario['nombre'] ?? '');
            $apellidoCliente = trim($usuario['apellido'] ?? '');
            $nombreCompleto = trim("$nombreCliente $apellidoCliente") ?: 'Cliente Universitario';
            $rolLabel = 'Estudiante - Cliente';

            $datos_cliente = [
                'nombre_cliente'   => $nombreCliente,
                'apellido_cliente' => $apellidoCliente,
                'nombre_completo'  => $nombreCompleto,
                'rol_label'        => $rolLabel,
                'telefono_cliente' => $usuario['telefono'] ?? '',
                'email_cliente'    => $usuario['email'] ?? ''
            ];
        } else {
            $nombreCliente = trim($_SESSION['nombre_cliente'] ?? 'Cliente');
            $apellidoCliente = trim($_SESSION['apellido_cliente'] ?? '');
            $nombreCompleto = trim("$nombreCliente $apellidoCliente") ?: 'Cliente Universitario';
            $rolLabel = 'Estudiante - Cliente';

            $datos_cliente = [
                'nombre_cliente'   => $nombreCliente,
                'apellido_cliente' => $apellidoCliente,
                'nombre_completo'  => $nombreCompleto,
                'rol_label'        => $rolLabel,
                'telefono_cliente' => '',
                'email_cliente'    => ''
            ];
        }
        $_SESSION['datos_cliente'] = $datos_cliente;
    } catch (Exception $e) {
        error_log("Error cargando cliente de MongoDB: " . $e->getMessage());
        $nombreCliente = trim($_SESSION['nombre_cliente'] ?? 'Cliente');
        $apellidoCliente = trim($_SESSION['apellido_cliente'] ?? '');
        $nombreCompleto = trim("$nombreCliente $apellidoCliente") ?: 'Cliente Universitario';
        $rolLabel = 'Estudiante - Cliente';

        $datos_cliente = [
            'nombre_cliente'   => $nombreCliente,
            'apellido_cliente' => $apellidoCliente,
            'nombre_completo'  => $nombreCompleto,
            'rol_label'        => $rolLabel,
            'telefono_cliente' => '',
            'email_cliente'    => ''
        ];
    }
}
?>