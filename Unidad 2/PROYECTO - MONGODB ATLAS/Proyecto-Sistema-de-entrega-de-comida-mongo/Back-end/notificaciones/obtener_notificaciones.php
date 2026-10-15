<?php
@ini_set('display_errors', '0');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

while (ob_get_level() > 0) {
    @ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

$tipoSolicitado = $_GET['tipo'] ?? null;

if ($tipoSolicitado === 'vendedor') {
    $idUsuario = $_SESSION['id_vendedor'] ?? null;
    $tipoUsuario = 'vendedor';
} elseif ($tipoSolicitado === 'cliente') {
    $idUsuario = $_SESSION['id_cliente'] ?? null;
    $tipoUsuario = 'cliente';
} else {
    // Si no se especifica tipo explícito, inferir según sesión activa
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
        'status'          => 'unauthenticated',
        'notificaciones'  => [],
        'total_no_leidas' => 0
    ]);
    exit;
}

// Liberar candado de sesión
session_write_close();

require_once dirname(__DIR__) . '/servicios/NotificacionService.php';

use Snackver\Servicios\NotificacionService;

// Sincronizar pedidos pendientes en tiempo real según el rol
if ($tipoUsuario === 'cliente') {
    NotificacionService::sincronizarPedidosCliente($idUsuario);
} elseif ($tipoUsuario === 'vendedor') {
    NotificacionService::sincronizarPedidosVendedor($idUsuario);
}

$resultado = NotificacionService::obtenerNotificaciones($idUsuario, 20, $tipoUsuario);

$withOrders = ($_GET['with_orders'] ?? '1') !== '0';

// Obtener estados actuales y estadísticas de pedidos recientes solo si se solicitan (with_orders != 0)
$pedidosEstados = [];
$stats = null;

if ($withOrders) {
    try {
        $mongoDb = MongoConexion::getConexion();
        $colPedidos = $mongoDb->selectCollection('pedidos');
        $colUsuarios = $mongoDb->selectCollection('usuarios');
        $colProductos = $mongoDb->selectCollection('productos');

    $idUsuarioStr = (string)$idUsuario;
    $filtroPedidos = [];
    $campoRol = ($tipoUsuario === 'vendedor') ? 'id_vendedor' : 'id_cliente';

    if (preg_match('/^[a-f\d]{24}$/i', $idUsuarioStr)) {
        $filtroPedidos = ['$or' => [
            [$campoRol => new MongoDB\BSON\ObjectId($idUsuarioStr)],
            [$campoRol => $idUsuarioStr],
            [$campoRol => (int)$idUsuario]
        ]];
    } else {
        $filtroPedidos = ['$or' => [
            [$campoRol => (int)$idUsuario],
            [$campoRol => $idUsuarioStr]
        ]];
    }

    if ($tipoUsuario === 'vendedor') {
        $countPendientes = $colPedidos->countDocuments(array_merge($filtroPedidos, ['estadopedido' => 'Pendiente']));
        $countTotal = $colPedidos->countDocuments($filtroPedidos);
        $countProductos = $colProductos->countDocuments($filtroPedidos);
        $stats = [
            'pendientes'      => $countPendientes,
            'total_general'   => $countTotal,
            'total_productos' => $countProductos
        ];
    }

    $cursorPedidos = $colPedidos->find(
        $filtroPedidos,
        [
            'projection' => [
                'id_pedido_num'  => 1,
                'estadopedido'   => 1,
                'total'          => 1,
                'fechapedido'    => 1,
                'items'          => 1,
                'id_cliente'     => 1,
                'id_vendedor'    => 1,
                'metodopago'     => 1,
                'referenciapago' => 1,
                '_id'            => 1
            ],
            'sort'  => ['fechapedido' => -1],
            'limit' => 20
        ]
    )->toArray();

    // Mapear nombres de clientes/vendedores
    $relatedIds = [];
    foreach ($cursorPedidos as $ped) {
        $relId = ($tipoUsuario === 'vendedor') ? ($ped['id_cliente'] ?? null) : ($ped['id_vendedor'] ?? null);
        if ($relId) {
            $relatedIds[] = (is_string($relId) && preg_match('/^[a-f\d]{24}$/i', $relId)) 
                ? new MongoDB\BSON\ObjectId($relId) : $relId;
        }
    }

    $namesMap = [];
    if (!empty($relatedIds)) {
        $docsRel = $colUsuarios->find(['_id' => ['$in' => array_values(array_unique($relatedIds, SORT_REGULAR))]]);
        foreach ($docsRel as $u) {
            $namesMap[(string)$u['_id']] = trim(($u['nombre'] ?? '') . ' ' . ($u['apellido'] ?? ''));
        }
    }

    foreach ($cursorPedidos as $ped) {
        $items = $ped['items'] ?? [];
        $itemsStr = [];
        if (is_iterable($items)) {
            foreach ($items as $it) {
                $itemsStr[] = ($it['nombreproducto'] ?? 'Platillo') . ' x' . ($it['cantidad'] ?? 1);
            }
        }

        $fechaFormateada = 'Hoy';
        if (isset($ped['fechapedido']) && $ped['fechapedido'] instanceof MongoDB\BSON\UTCDateTime) {
            $fechaFormateada = date('d/m/Y h:i A', $ped['fechapedido']->toDateTime()->getTimestamp());
        }

        $relIdStr = (string)(($tipoUsuario === 'vendedor') ? ($ped['id_cliente'] ?? '') : ($ped['id_vendedor'] ?? ''));
        $nombreRel = $namesMap[$relIdStr] ?? (($tipoUsuario === 'vendedor') ? 'Cliente' : 'Vendedor');

        $pedidosEstados[] = [
            'id'                  => (string)($ped['id_pedido_num'] ?? $ped['_id']),
            'id_num'              => (string)($ped['id_pedido_num'] ?? ''),
            'id_oid'              => (string)$ped['_id'],
            'estado'              => $ped['estadopedido'] ?? 'Pendiente',
            'total'               => '$' . number_format((float)($ped['total'] ?? 0), 2),
            'total_raw'           => (float)($ped['total'] ?? 0),
            'fechapedido'         => $fechaFormateada,
            'productos'           => implode(', ', $itemsStr),
            'cliente_o_vendedor'  => $nombreRel,
            'cliente'             => ($tipoUsuario === 'vendedor') ? $nombreRel : '',
            'vendedor'            => ($tipoUsuario === 'cliente') ? $nombreRel : '',
            'metodopago'          => $ped['metodopago'] ?? 'Efectivo',
            'referenciapago'      => $ped['referenciapago'] ?? ''
        ];
    }
    } catch (Exception $e) {
        // Si falla la proyección de pedidos, no bloquea las notificaciones
    }
}

$responsePayload = [
    'status'          => 'success',
    'notificaciones'  => $resultado['notificaciones'],
    'total_no_leidas' => $resultado['total_no_leidas'],
    'pedidos'         => $pedidosEstados
];
if ($stats !== null) {
    $responsePayload['stats'] = $stats;
}

echo json_encode($responsePayload);
exit;
