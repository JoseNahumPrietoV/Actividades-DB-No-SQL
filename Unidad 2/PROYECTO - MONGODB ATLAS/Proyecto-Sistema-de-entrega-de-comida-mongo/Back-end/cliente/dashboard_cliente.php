<?php

require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';


// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? ''; // Mantengo 'mensaje_perfil' si ya lo usas para mostrar el resultado
unset($_SESSION['mensaje_perfil']); // Limpiar la variable después de mostrar

// Liberar candado de sesión inmediatamente para permitir recargas fluidas
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - Snackver</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css?v=2.3">
    <link rel="stylesheet" href="../css/resumen-cliente.css?v=2.3">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_cliente.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>

            <div class="auth-buttons">

                <div class="notification-bell" id="bellIcon">
                    <i class="fas fa-bell"></i>

                    <span id="notification-badge"></span>

                    <div id="notification-dropdown">
                        <div class="dropdown-header">
                            <span>Notificaciones</span>
                            <button type="button" class="btn-leer-todas" id="btnMarcarTodasLeidas" title="Marcar todas como leídas">
                                <i class="fas fa-check-double"></i> Leer todas
                            </button>
                        </div>
                        <div id="notification-list">
                            <p class="no-notif">No hay notificaciones nuevas</p>
                        </div>
                    </div>
                </div>

                <a href="carrito.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> 
                </a>

            </div>
        </div>
    </header>




    <main class="dashboard-main-content">
        <div class="container">
            <?php if (!empty($mensaje_perfil)): ?>
                <div class="alert" style="color: white; background-color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($mensaje_perfil); ?>
                </div>
            <?php endif; ?>
            <div class="welcome-header">
                <h1>Tu Cuenta <?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['rol_label'] ?? 'Estudiante - Cliente'); ?></p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php" class="active"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card">
                   
                        <h3>Pedidos Recientes</h3>

                        <?php
                        $idClienteLogueado = $_SESSION['id_cliente'] ?? null;
                        
                        // Liberar candado de sesión inmediatamente para permitir recargas fluidas
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_write_close();
                        }

                        $pedidosRecientes = [];

                        try {
                            $mongoDb = MongoConexion::getConexion();
                            $colPedidos  = $mongoDb->selectCollection('pedidos');
                            $colUsuarios = $mongoDb->selectCollection('usuarios');

                            $filtroCli = [];
                            if (is_string($idClienteLogueado) && preg_match('/^[a-f\d]{24}$/i', $idClienteLogueado)) {
                                $filtroCli = ['$or' => [
                                    ['id_cliente' => new MongoDB\BSON\ObjectId($idClienteLogueado)],
                                    ['id_cliente' => (string)$idClienteLogueado],
                                    ['id_cliente' => (int)$idClienteLogueado]
                                ]];
                            } else {
                                $filtroCli = ['$or' => [
                                    ['id_cliente' => (int)$idClienteLogueado],
                                    ['id_cliente' => (string)$idClienteLogueado]
                                ]];
                            }

                            $cursor = $colPedidos->find($filtroCli, [
                                'sort' => ['fechapedido' => -1],
                                'limit' => 5,
                                'projection' => [
                                    'id_pedido_num' => 1,
                                    'id_vendedor'   => 1,
                                    'fechapedido'   => 1,
                                    'total'         => 1,
                                    'estadopedido'  => 1,
                                    'items'         => 1
                                ]
                            ])->toArray();

                            // 1. Recolectar todos los IDs de vendedores únicos de los pedidos recientes
                            $vendorIds = [];
                            foreach ($cursor as $p) {
                                $idVen = $p['id_vendedor'] ?? null;
                                if ($idVen) {
                                    $vendorIds[] = (is_string($idVen) && preg_match('/^[a-f\d]{24}$/i', $idVen)) 
                                        ? new MongoDB\BSON\ObjectId($idVen) : $idVen;
                                }
                            }

                            $vendedoresMap = [];
                            if (!empty($vendorIds)) {
                                $uniqueVen = array_values(array_unique($vendorIds, SORT_REGULAR));
                                $docsVen = $colUsuarios->find(
                                    ['_id' => ['$in' => $uniqueVen]],
                                    ['projection' => [
                                        'nombre'   => 1,
                                        'apellido' => 1
                                    ]]
                                );
                                foreach ($docsVen as $v) {
                                    $vendedoresMap[(string)$v['_id']] = $v;
                                }
                            }

                            // 2. Mapear en memoria instantáneamente
                            foreach ($cursor as $p) {
                                $idVen = $p['id_vendedor'] ?? null;
                                $nombreVen = 'Vendedor';
                                $apellidoVen = '';
                                if ($idVen) {
                                    $idVenStr = (string)$idVen;
                                    if (isset($vendedoresMap[$idVenStr])) {
                                        $venDoc = $vendedoresMap[$idVenStr];
                                        $nombreVen = $venDoc['nombre'] ?? 'Vendedor';
                                        $apellidoVen = $venDoc['apellido'] ?? '';
                                    }
                                }

                                $items = $p['items'] ?? [];
                                $itemsArr = [];
                                foreach ($items as $it) {
                                    $itemsArr[] = ($it['nombreproducto'] ?? 'Platillo') . ' x' . ($it['cantidad'] ?? 1);
                                }

                                $fechaStr = 'Hoy';
                                if (isset($p['fechapedido']) && $p['fechapedido'] instanceof MongoDB\BSON\UTCDateTime) {
                                    $fechaStr = date('d M Y', $p['fechapedido']->toDateTime()->getTimestamp());
                                }

                                $pedidosRecientes[] = [
                                    'idpedido'          => (string)($p['id_pedido_num'] ?? $p['_id']),
                                    'fechapedido'       => $fechaStr,
                                    'total'             => (float)($p['total'] ?? 0),
                                    'estadopedido'      => $p['estadopedido'] ?? 'Pendiente',
                                    'nombre_vendedor'   => $nombreVen,
                                    'apellido_vendedor' => $apellidoVen,
                                    'lista_productos'   => implode(', ', $itemsArr)
                                ];
                            }
                        } catch (Exception $e) {
                            error_log("Error cargando pedidos cliente en dashboard: " . $e->getMessage());
                        }
                        ?>

                        <div class="order-history" id="contenedor-pedidos-cliente">
                            <?php
                            if (!empty($pedidosRecientes)) {
                                foreach ($pedidosRecientes as $fila) {
                                    $id_pedido_formato = "#ORD-" . $fila['idpedido'];
                                    $fecha_formato = $fila['fechapedido'];
                                    $total_formato = "$" . number_format($fila['total'], 2);
                                    $estado_pedido = htmlspecialchars($fila['estadopedido']);
                                    $nombre_vendedor = htmlspecialchars($fila['nombre_vendedor']);
                                    $apellido_vendedor = htmlspecialchars($fila['apellido_vendedor']);

                                    $clase_estado = "status-pending";
                                    if ($estado_pedido == "En preparación") $clase_estado = "status-preparing";
                                    if ($estado_pedido == "Listo") $clase_estado = "status-ready";
                                    if ($estado_pedido == "Entregado") $clase_estado = "status-completed";
                                    if ($estado_pedido == "Cancelado" || $estado_pedido == "Rechazado") $clase_estado = "status-rejected";

                                    $productos_raw = $fila['lista_productos'];

                            ?>

                                    <div class="order-card" data-idpedido="<?php echo $fila['idpedido']; ?>">
                                        <div class="order-header">
                                            <div>
                                                <h4>Pedido <?php echo htmlspecialchars($id_pedido_formato); ?></h4>
                                                <p>Realizado el <?php echo htmlspecialchars($fecha_formato); ?></p>
                                            </div>
                                            <div>
                                                <span id="status-order-<?php echo $fila['idpedido']; ?>" class="order-status <?php echo $clase_estado; ?>"><?php echo $estado_pedido; ?></span>
                                            </div>
                                        </div>

                                        <div class="order-items">
                                            <?php
                                            // Asumo que tu consulta SELECT trae los campos nombre_vendedor y apellido_vendedor,
                                            // o que ya definiste $apellido_vendedor antes del loop.
                                            $nombre_completo_vendedor = htmlspecialchars($fila['nombre_vendedor']) . ' ' . htmlspecialchars($fila['apellido_vendedor']);

                                            // Si la consulta trae el nombre completo del vendedor en un solo campo (como lo hace el cliente):
                                            // $nombre_completo_vendedor = htmlspecialchars($fila['nombre_completo_vendedor']);

                                            // --- 1. Muestra la lista de productos (Línea superior) ---
                                            echo '<div class="order-item">';
                                            echo '    <span>Productos: ' . htmlspecialchars($productos_raw) . '</span>';
                                            echo '</div>';

                                            // --- 2. Vendedor (Izquierda) y Total (Derecha) ---

                                            echo '<div class="order-item order-item-details">'; // Nueva clase para flexibilidad de diseño

                                            // Vendedor a la IZQUIERDA
                                            echo '    <span>Vendedor: <strong>' . $nombre_completo_vendedor . '</strong></span>';

                                            echo '</div>';
                                            ?>
                                        </div>
                                        <div class="order-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                            <div class="order-item order-item-details">
                                                <span class="order-total-inline"><strong>Total: <?php echo $total_formato; ?></strong></span>
                                            </div>

                                            <div id="acciones-order-<?php echo $fila['idpedido']; ?>" style="display: flex; gap: 10px; justify-content: flex-end;">
                                                <?php if ($estado_pedido == 'Pendiente'): ?>
                                                    <form action="cancelar_pedido_cliente.php" method="POST" style="display:inline; margin: 0;">
                                                        <input type="hidden" name="idpedido_a_cancelar" value="<?php echo $fila['idpedido']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Cancelar</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if ($estado_pedido == 'Entregado' || $estado_pedido == 'Listo' || $estado_pedido == 'Cancelado'): ?>
                                                    <a href="repetir_pedido.php?idpedido=<?php echo $fila['idpedido']; ?>" class="btn btn-primary">Volver a Pedir</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div> <?php
                                        } // Fin del while
                                    } else {
                                        echo '<div class="order-card" style="text-align: center; color: #888; padding: 20px;">
                                        No se encontraron pedidos con estatus "En Preparación".
                                      </div>';
                                    }

                                    ?>
    </main>

    <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>