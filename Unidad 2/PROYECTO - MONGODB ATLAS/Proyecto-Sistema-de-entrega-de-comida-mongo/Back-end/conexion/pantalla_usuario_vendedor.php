<?php
// Back-end/conexion/pantalla_usuario_vendedor.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/conexion_mongo.php';

// 1. Verificar sesión
if (!isset($_SESSION['id_vendedor'])) {
    header('Location: ../../Front-end/login.php');
    exit();
}

$id_vendedor = $_SESSION['id_vendedor'];
$datos_vendedor = [];

// Micro-caché de sesión: Si ya tenemos los datos de vendedor en sesión, usarlos de inmediato
if (!empty($_SESSION['datos_vendedor']) && is_array($_SESSION['datos_vendedor'])) {
    $datos_vendedor = $_SESSION['datos_vendedor'];
    if (!isset($datos_vendedor['foto_negocio']) || empty($datos_vendedor['foto_negocio'])) {
        try {
            /** @var \MongoDB\Database $mongoDb */
            $mongoDb = MongoConexion::getConexion();
            $colUsuarios = $mongoDb->selectCollection('usuarios');
            $filtroSync = [];
            if (is_string($id_vendedor) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
                $filtroSync = ['_id' => new MongoDB\BSON\ObjectId($id_vendedor)];
            } else {
                $filtroSync = ['$or' => [['_id' => (int)$id_vendedor], ['_id' => (string)$id_vendedor]]];
            }
            $uDoc = $colUsuarios->findOne($filtroSync, ['projection' => ['tienda.foto_negocio' => 1]]);
            if (!empty($uDoc['tienda']['foto_negocio'])) {
                $datos_vendedor['foto_negocio'] = $uDoc['tienda']['foto_negocio'];
                $_SESSION['datos_vendedor']['foto_negocio'] = $datos_vendedor['foto_negocio'];
            } else {
                $datos_vendedor['foto_negocio'] = '';
            }
        } catch (\Throwable $e) {
            $datos_vendedor['foto_negocio'] = $datos_vendedor['foto_negocio'] ?? '';
        }
    }
    try {
        /** @var \MongoDB\Database $mongoDb */
        $mongoDb = MongoConexion::getConexion();
        /** @var \MongoDB\Database $conn */
        $conn = $mongoDb;
    } catch (\Throwable $e) {
        // Conexión perezosa si se requiere
    }
} else {
    try {
        /** @var \MongoDB\Database $mongoDb */
        $mongoDb = MongoConexion::getConexion();
        /** @var \MongoDB\Database $conn */
        $conn = $mongoDb;
        $colUsuarios = $mongoDb->selectCollection('usuarios');

        $filtro = [];
        if (is_string($id_vendedor) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
            $filtro = ['_id' => new MongoDB\BSON\ObjectId($id_vendedor)];
        } else {
            $filtro = ['$or' => [
                ['_id' => (int)$id_vendedor],
                ['_id' => (string)$id_vendedor]
            ]];
        }

        $usuario = $colUsuarios->findOne($filtro, [
            'projection' => [
                'nombre'        => 1,
                'apellido'      => 1,
                'telefono'      => 1,
                'email'         => 1,
                'tienda'        => 1,
                'fecharegistro' => 1
            ]
        ]);

        if ($usuario) {
            $tienda = $usuario['tienda'] ?? [];
            $nombreVendedor = trim($usuario['nombre'] ?? '');
            $apellidoVendedor = trim($usuario['apellido'] ?? '');
            $nombreCompleto = trim("$nombreVendedor $apellidoVendedor") ?: 'Vendedor';
            $rolLabel = 'Vendedor';

            $datos_vendedor = [
                'nombre_vendedor'          => $nombreVendedor,
                'apellido_vendedor'        => $apellidoVendedor,
                'nombre_completo'          => $nombreCompleto,
                'rol_label'                => $rolLabel,
                'telefono_vendedor'        => $usuario['telefono'] ?? '',
                'email_vendedor'           => $usuario['email'] ?? '',
                'nombre_negocio'           => $tienda['nombre_negocio'] ?? '',
                'descripcion'              => $tienda['descripcion'] ?? '',
                'dirección'                => $tienda['direccion'] ?? ($tienda['dirección'] ?? ''),
                'foto_negocio'             => $tienda['foto_negocio'] ?? '',
                'fechaRegistro'            => isset($usuario['fecharegistro']) ? date('Y-m-d H:i:s', $usuario['fecharegistro']->toDateTime()->getTimestamp()) : date('Y-m-d H:i:s')
            ];
        } else {
            $nombreVendedor = trim($_SESSION['nombre_vendedor'] ?? 'Vendedor');
            $apellidoVendedor = trim($_SESSION['apellido_vendedor'] ?? '');
            $nombreCompleto = trim("$nombreVendedor $apellidoVendedor") ?: 'Vendedor';
            $rolLabel = 'Vendedor';

            $datos_vendedor = [
                'nombre_vendedor'          => $nombreVendedor,
                'apellido_vendedor'        => $apellidoVendedor,
                'nombre_completo'          => $nombreCompleto,
                'rol_label'                => $rolLabel,
                'telefono_vendedor'        => '',
                'email_vendedor'           => '',
                'nombre_negocio'           => 'Mi Tienda',
                'descripcion'              => '',
                'dirección'                => '',
                'fechaRegistro'            => date('Y-m-d H:i:s')
            ];
        }
        $_SESSION['datos_vendedor'] = $datos_vendedor;
    } catch (Exception $e) {
        error_log("Error cargando vendedor de MongoDB: " . $e->getMessage());
        $nombreVendedor = trim($_SESSION['nombre_vendedor'] ?? 'Vendedor');
        $apellidoVendedor = trim($_SESSION['apellido_vendedor'] ?? '');
        $nombreCompleto = trim("$nombreVendedor $apellidoVendedor") ?: 'Vendedor';
        $rolLabel = 'Vendedor';

        $datos_vendedor = [
            'nombre_vendedor'          => $nombreVendedor,
            'apellido_vendedor'        => $apellidoVendedor,
            'nombre_completo'          => $nombreCompleto,
            'rol_label'                => $rolLabel,
            'telefono_vendedor'        => '',
            'email_vendedor'           => '',
            'nombre_negocio'           => 'Mi Tienda',
            'descripcion'              => '',
            'dirección'                => '',
            'fechaRegistro'            => date('Y-m-d H:i:s')
        ];
    }
}
?>