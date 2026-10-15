<?php

// 1. INCLUIR EL ARCHIVO DE CONEXIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// Liberar candado de sesión inmediatamente para permitir recargas fluidas
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// 2. FILTROS
$filtro_estado = $_GET['estado'] ?? 'Todos';
$filtro_fecha = $_GET['fecha'] ?? '';

// 3. CONSULTA EN MONGODB ATLAS
$listaPedidosCliente = [];

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

    $criterios = [$filtroCli];

    if ($filtro_estado !== 'Todos' && !empty($filtro_estado)) {
        $criterios[] = ['estadopedido' => $filtro_estado];
    }

    if (!empty($filtro_fecha)) {
        $startDay = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha . ' 00:00:00') * 1000);
        $endDay   = new MongoDB\BSON\UTCDateTime(strtotime($filtro_fecha . ' 23:59:59') * 1000);
        $criterios[] = ['fechapedido' => ['$gte' => $startDay, '$lte' => $endDay]];
    }

    $filtroFinal = (count($criterios) > 1) ? ['$and' => $criterios] : $filtroCli;

    $cursor = $colPedidos->find($filtroFinal, [
        'sort' => ['fechapedido' => -1],
        'projection' => [
            'id_pedido_num' => 1,
            'id_vendedor'   => 1,
            'fechapedido'   => 1,
            'metodopago'    => 1,
            'total'         => 1,
            'estadopedido'  => 1,
            'items'         => 1
        ]
    ])->toArray();

    // 1. Recolectar todos los IDs de vendedores únicos para una sola consulta masiva
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

    // 2. Mapear pedidos y productos en memoria al instante
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

        $listaPedidosCliente[] = [
            'idpedido'          => (string)($p['id_pedido_num'] ?? $p['_id']),
            'fechapedido'       => $fechaStr,
            'metodopago'        => $p['metodopago'] ?? 'Efectivo',
            'nombre_vendedor'   => $nombreVen,
            'apellido_vendedor' => $apellidoVen,
            'total'             => (float)($p['total'] ?? 0),
            'estadopedido'      => $p['estadopedido'] ?? 'Pendiente',
            'lista_productos'   => implode(', ', $itemsArr)
        ];
    }
} catch (Exception $e) {
    error_log("Error cargando MisPedidos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Snackver</title>
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
                <h1>Historial de Pedidos</h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_completo'] ?? 'Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['rol_label'] ?? 'Estudiante - Cliente'); ?></p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php" class="active"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-card">
                    <h3>Mis Pedidos</h3>

                    <div class="filter-container">
                        <form action="" method="GET">
                            <div class="filter-controls">
                                <select name="estado" class="form-control filter-select">
                                    <option value="Todos" <?php if ($filtro_estado == 'Todos') echo 'selected'; ?>>Todos</option>
                                    <option value="Pendiente" <?php if ($filtro_estado == 'Pendiente') echo 'selected'; ?>>Pendientes</option>
                                    <option value="En preparación" <?php if ($filtro_estado == 'En preparación') echo 'selected'; ?>>En preparación</option>
                                    <option value="Listo" <?php if ($filtro_estado == 'Listo') echo 'selected'; ?>>Listo</option>
                                    <option value="Entregado" <?php if ($filtro_estado == 'Entregado') echo 'selected'; ?>>Entregado</option>
                                    <option value="Rechazado" <?php if ($filtro_estado == 'Rechazado') echo 'selected'; ?>>Rechazado</option>
                                    <option value="Cancelado" <?php if ($filtro_estado == 'Cancelado') echo 'selected'; ?>>Cancelado</option>
                                </select>
                                <button type="submit" class="btn btn-primary" style="height: 42px; display: flex; align-items: center;">
                                    <i class="fas fa-filter" style="margin-right: 5px;"></i> Filtrar
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="order-history">
                        <?php
                        if (!empty($listaPedidosCliente)) {
                            foreach ($listaPedidosCliente as $fila) {
                                // Variables
                                $id_pedido_formato = "#ORD-" . $fila['idpedido'];
                                $fecha_formato = $fila['fechapedido'];
                                $total_formato = "$" . number_format($fila['total'], 2);
                                $estado_pedido = htmlspecialchars($fila['estadopedido']);
                                $nombre_completo_vendedor = htmlspecialchars($fila['nombre_vendedor']) . ' ' . htmlspecialchars($fila['apellido_vendedor']);

                                // Clases CSS
                                $clase_estado = "status-pending";
                                if ($estado_pedido == "En preparación") $clase_estado = "status-preparing";
                                if ($estado_pedido == "Listo") $clase_estado = "status-ready";
                                if ($estado_pedido == "Entregado") $clase_estado = "status-completed";
                                if ($estado_pedido == "Cancelado" || $estado_pedido == "Rechazado") $clase_estado = "status-rejected";

                                $lista_productos = $fila['lista_productos'];
                        ?>

                                <div class="order-card" data-idpedido="<?php echo $fila['idpedido']; ?>">
                                    <div class="order-header">
                                        <div>
                                            <h4>Pedido <?php echo $id_pedido_formato; ?></h4>
                                            <p><?php echo $fecha_formato; ?></p>
                                        </div>
                                        <div>
                                            <span id="status-order-<?php echo $fila['idpedido']; ?>" class="order-status <?php echo $clase_estado; ?>"><?php echo $estado_pedido; ?></span>
                                        </div>
                                    </div>

                                    <div class="order-items">
                                        <div class="order-item">
                                            <span>Productos: <?php echo htmlspecialchars($lista_productos); ?></span>
                                        </div>
                                        <div class="order-item order-item-details">
                                            <span>Vendedor: <strong><?php echo $nombre_completo_vendedor; ?></strong></span>
                                        </div>
                                    </div>

                                    <div class="order-footer">
                                        <div class="order-item order-item-details" style="padding-top: 10px;">
                                            <span class="order-total-inline"><strong>Total: <?php echo $total_formato; ?></strong></span>
                                        </div>

                                        <div id="acciones-order-<?php echo $fila['idpedido']; ?>" style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">

                                            <?php if ($estado_pedido == 'Pendiente'): ?>

                                                <form action="cancelar_pedido_cliente.php" method="POST" style="margin-bottom: 5px;">
                                                    <input type="hidden" name="idpedido_a_cancelar" value="<?php echo $fila['idpedido']; ?>">
                                                    <button type="submit" class="btn btn-danger" style="width: 100%;">Cancelar</button>
                                                </form>

                                                <?php
                                                // Recuperamos el método de pago
                                                // IMPORTANTE: Asegúrate de que esta columna exista en tu SELECT de arriba
                                                $metodo = $fila['metodopago'] ?? '';
                                                ?>

                                                <?php if ($metodo == 'Transferencia Bancaria' || $metodo == 'Transferencia'): ?>

                                                    <button onclick="simularPagoBanco(<?php echo $fila['idpedido']; ?>)"
                                                        class="btn"
                                                        style="background-color: #6c5ce7; color: white; border: none; width: 100%; margin-top: 5px;">
                                                        <i class="fas fa-bolt"></i> Pagar (Simular SPEI)
                                                    </button>

                                                <?php endif; ?>

                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<div class="order-card" style="text-align: center; color: #888; padding: 20px;">No se encontraron pedidos.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- FUNCIÓN DE SIMULACIÓN DE PAGO (RUTA A) ---
        function simularPagoBanco(idPedido) {
            if (!confirm("🏦 BANCO DE PRUEBAS:\n\n¿Autorizar pago para este pedido?\n(Esto cambiará el estado a 'En preparación' automáticamente)")) {
                return;
            }

            // Cambiamos el texto del botón temporalmente
            const btn = event.target.closest('button'); // Asegura agarrar el botón si clicaste el ícono
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            btn.disabled = true;

            // Llamada al simulador de webhook en el mismo directorio
            fetch('webhook_simulado.php?idpedido=' + idPedido)
                .then(response => response.text())
                .then(data => {
                    if (data.includes("Exito") || data.includes("actualizado") || data.includes("Pago Confirmado")) {
                        const spanEstado = document.getElementById('status-order-' + idPedido);
                        if (spanEstado) {
                            spanEstado.textContent = 'En preparación';
                            spanEstado.className = 'order-status status-preparing';
                        }
                        const contenedorAcciones = document.getElementById('acciones-order-' + idPedido);
                        if (contenedorAcciones) {
                            contenedorAcciones.innerHTML = '';
                        }
                        if (typeof window.consultarNotificaciones === 'function') {
                            window.consultarNotificaciones();
                        }
                        alert("✅ ¡PAGO APROBADO!\n\nEl sistema bancario ha confirmado la transferencia.");
                    } else {
                        const textoLimpio = data.replace(/<[^>]*>?/gm, '').trim();
                        alert("⚠️ Resultado del simulador:\n" + textoLimpio);
                        btn.innerHTML = textoOriginal;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("❌ Error: No se pudo conectar con el simulador de pagos.");
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                });
        }

        // Filtrado en tiempo real sin recargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const formFiltro = document.querySelector('.filter-container form');
            const selectEstado = document.querySelector('.filter-container select[name="estado"]');

            function aplicarFiltroCliente() {
                if (!selectEstado) return;
                const est = selectEstado.value;
                const cards = document.querySelectorAll('.order-history .order-card[data-idpedido]');
                cards.forEach(card => {
                    const badge = card.querySelector('.order-status');
                    const estadoCard = badge ? badge.textContent.trim() : '';
                    if (est === 'Todos' || estadoCard === est) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            if (formFiltro) {
                formFiltro.addEventListener('submit', function(e) {
                    e.preventDefault();
                    aplicarFiltroCliente();
                });
            }
            if (selectEstado) {
                selectEstado.addEventListener('change', aplicarFiltroCliente);
            }
            window.addEventListener('snackver:pedidos_actualizados', aplicarFiltroCliente);
        });
    </script>
    <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>