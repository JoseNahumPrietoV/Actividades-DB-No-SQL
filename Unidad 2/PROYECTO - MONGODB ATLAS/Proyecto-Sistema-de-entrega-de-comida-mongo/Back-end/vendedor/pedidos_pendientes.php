<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';

// Obtenemos el ID del vendedor
$id_vendedor = $_SESSION['id_vendedor'];

// Liberar candado de sesión inmediatamente para permitir recargas y concurrencia fluida
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 2. LÓGICA DEL FILTRO ---
$estado_filtrar = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : 'Todos';

// --- 3. CONSULTA PRINCIPAL DE PEDIDOS (MONGODB ATLAS) ---
$pedidos_vendedor = [];
try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos  = $mongoDb->selectCollection('pedidos');
    $colUsuarios = $mongoDb->selectCollection('usuarios');

    $filtroVendedor = [];
    if (is_string($id_vendedor) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => new MongoDB\BSON\ObjectId($id_vendedor)],
            ['id_vendedor' => (string)$id_vendedor],
            ['id_vendedor' => (int)$id_vendedor]
        ]];
    } else {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => (int)$id_vendedor],
            ['id_vendedor' => (string)$id_vendedor]
        ]];
    }

    $criterios = [$filtroVendedor];
    if ($estado_filtrar !== 'Todos') {
        $criterios[] = ['estadopedido' => $estado_filtrar];
    }
    $filtro = (count($criterios) > 1) ? ['$and' => $criterios] : $filtroVendedor;

    $cursor = $colPedidos->find($filtro, [
        'sort' => ['fechapedido' => -1],
        'projection' => [
            'id_pedido_num'  => 1,
            'id_cliente'     => 1,
            'items'          => 1,
            'fechapedido'    => 1,
            'total'          => 1,
            'estadopedido'   => 1,
            'referenciapago' => 1,
            'metodopago'     => 1
        ]
    ])->toArray();

    // 1. Recolectar todos los IDs de clientes únicos para una sola consulta masiva
    $clientIds = [];
    foreach ($cursor as $p) {
        $idCli = $p['id_cliente'] ?? null;
        if ($idCli) {
            $clientIds[] = (is_string($idCli) && preg_match('/^[a-f\d]{24}$/i', $idCli)) 
                ? new MongoDB\BSON\ObjectId($idCli) : $idCli;
        }
    }

    $clientesMap = [];
    if (!empty($clientIds)) {
        $uniqueCli = array_values(array_unique($clientIds, SORT_REGULAR));
        $docsCli = $colUsuarios->find(
            ['_id' => ['$in' => $uniqueCli]],
            ['projection' => [
                'nombre'   => 1,
                'apellido' => 1
            ]]
        );
        foreach ($docsCli as $u) {
            $clientesMap[(string)$u['_id']] = $u;
        }
    }

    // 2. Mapear pedidos y clientes en memoria al instante
    foreach ($cursor as $p) {
        $idCli = $p['id_cliente'] ?? null;
        $nombreCliente = 'Cliente';
        if ($idCli) {
            $idCliStr = (string)$idCli;
            if (isset($clientesMap[$idCliStr])) {
                $cliDoc = $clientesMap[$idCliStr];
                $nombreCliente = trim(($cliDoc['nombre'] ?? '') . ' ' . ($cliDoc['apellido'] ?? ''));
            }
        }

        $items = $p['items'] ?? [];
        $itemsStr = [];
        foreach ($items as $it) {
            $itemsStr[] = ($it['nombreproducto'] ?? 'Platillo') . ' x' . ($it['cantidad'] ?? 1);
        }

        $fechaStr = 'Hoy';
        if (isset($p['fechapedido']) && $p['fechapedido'] instanceof MongoDB\BSON\UTCDateTime) {
            $fechaStr = date('d/m/Y H:i', $p['fechapedido']->toDateTime()->getTimestamp());
        }

        $pedidos_vendedor[] = [
            'idpedido'                 => (string)($p['id_pedido_num'] ?? $p['_id']),
            'fechapedido_formateada'   => $fechaStr,
            'nombre_completo_cliente'  => $nombreCliente,
            'lista_productos'          => implode(', ', $itemsStr),
            'total'                    => (float)($p['total'] ?? 0),
            'estadopedido'             => $p['estadopedido'] ?? 'Pendiente',
            'referenciapago'           => $p['referenciapago'] ?? '',
            'metodopago'               => $p['metodopago'] ?? 'Efectivo'
        ];
    }
} catch (Exception $e) {
    error_log("Error cargando pedidos en pedidos_pendientes: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Pedidos - Snackver</title>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-vendedor.css?v=2.3">
    <link rel="stylesheet" href="../css/resumen-vendedor.css?v=2.3">
    <link rel="icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="shortcut icon" type="image/png" href="../img/logo%20-%20snackver.png?v=2.2">
    <link rel="apple-touch-icon" href="../img/logo%20-%20snackver.png?v=2.2">
 
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="dashboard_vendedor.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <nav></nav>
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

                <a href="../conexion/cerrar_sesion.php" class="btn btn-primary" title="Cerrar Sesión">
                    <i class="fas fa-power-off"></i>
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
                <p><?php echo ($estado_filtrar === 'Todos') ? 'Estás viendo: <strong>Todos los pedidos</strong>' : 'Estás viendo los pedidos en estado: <strong>' . htmlspecialchars($estado_filtrar) . '</strong>'; ?></p>
            </div>

            <div class="filter-section">
                <a href="dashboard_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>

                <form method="GET" action="pedidos_pendientes.php" class="filter-form" onsubmit="event.preventDefault();">
                    <label for="filtro_estado">Filtrar por:</label>
                    <select name="filtro_estado" id="filtro_estado" class="filter-select">
                        <option value="Todos" <?php if($estado_filtrar == 'Todos') echo 'selected'; ?>>📋 Todos los pedidos</option>
                        <option value="Pendiente" <?php if($estado_filtrar == 'Pendiente') echo 'selected'; ?>>🟡 Pendientes (Nuevos)</option>
                        <option value="En preparación" <?php if($estado_filtrar == 'En preparación') echo 'selected'; ?>>🟡 En preparación (Pagados)</option>
                        <option value="Listo" <?php if($estado_filtrar == 'Listo') echo 'selected'; ?>>🟢 Listos para entregar</option>
                        <option value="Entregado" <?php if($estado_filtrar == 'Entregado') echo 'selected'; ?>>🏁 Entregados (Historial)</option>
                        <option value="Cancelado" <?php if($estado_filtrar == 'Cancelado') echo 'selected'; ?>>🔴 Cancelados</option>
                        <option value="Rechazado" <?php if($estado_filtrar == 'Rechazado') echo 'selected'; ?>>🔴 Rechazados</option>
                    </select>
                </form>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-content" style="width: 100%;">
                    <div class="dashboard-card">
                        
                        <div class="order-history" id="contenedor-pedidos">
                            <?php
                            if (!empty($pedidos_vendedor)) {
                                foreach ($pedidos_vendedor as $fila) {

                                    // --- 1. PREPARAR DATOS ---
                                    $estado = htmlspecialchars($fila['estadopedido']);
                                    $id_pedido = "#ORD-" . htmlspecialchars($fila['idpedido']);
                                    $fecha_formateada = $fila['fechapedido_formateada'];
                                    $total_formateado = "$" . number_format($fila['total'], 2);
                                    $cliente = htmlspecialchars($fila['nombre_completo_cliente']);
                                    $productos = htmlspecialchars($fila['lista_productos']);
                                    $referenciapago = htmlspecialchars($fila['referenciapago']);
                                    $metodopago = htmlspecialchars($fila['metodopago']);
                                    
                                    // --- 2. LÓGICA DE COLORES ---
                                    $clase_badge = 'status-pending'; 
                                    if ($estado == 'En preparación') $clase_badge = 'status-preparing'; 
                                    if ($estado == 'Listo' || $estado == 'Entregado') $clase_badge = 'status-ready'; 
                                    if ($estado == 'Cancelado') $clase_badge = 'status-cancelado';
                                    if ($estado == 'Rechazado') $clase_badge = 'status-rechazado'; 

                            ?>

                                    <div class="order-card" data-idpedido="<?php echo htmlspecialchars($fila['idpedido']); ?>">
                                        <div class="order-header">
                                            <div>
                                                <h4>Pedido <?php echo $id_pedido; ?></h4>
                                                <p>Realizado el <?php echo $fecha_formateada; ?></p>
                                            </div>
                                            <div>
                                                <span id="status-<?php echo htmlspecialchars($fila['idpedido']); ?>" class="order-status <?php echo $clase_badge; ?>"><?php echo $estado; ?></span>
                                            </div>
                                        </div>

                                        <div class="order-items">
                                            <div class="order-item">
                                                <span><strong>Productos:</strong> <?php echo $productos; ?></span>
                                            </div>
                                            <div class="order-item order-item-details">
                                                <span><strong>Cliente:</strong> <?php echo $cliente; ?></span>
                                            </div>
                                            <div class="order-item order-item-details">
                                                <span><strong>Pago:</strong> <?php echo $metodopago; ?></span>
                                            </div>
                                            
                                            <?php if(!empty($referenciapago)): ?>
                                            <div class="order-item order-item-details">
                                                <span><strong>Ref:</strong> <?php echo $referenciapago; ?></span>
                                            </div>
                                            <?php endif; ?>

                                            <div class="order-item order-item-details" style="padding-top: 10px; border-top: 1px solid #eee; margin-top: 10px; display:flex; justify-content:space-between; align-items:center;">
                                                <span class="order-total-inline"><strong>Total: <?php echo $total_formateado; ?></strong></span>
                                                
                                                <?php if($estado == 'Pendiente'): ?>
                                                    <span style="font-size:12px; color:#e67e22;"><i class="fas fa-clock"></i> Esperando atención</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div> 
                            <?php
                                } 
                            } else {
                                echo '<div style="text-align: center; padding: 40px; color: #777;">
                                        <i class="fas fa-clipboard-check" style="font-size: 40px; margin-bottom: 10px; color: #ccc;"></i><br>' .
                                        ($estado_filtrar === 'Todos' ? 'No tienes pedidos registrados.' : 'No hay pedidos en estado "'.htmlspecialchars($estado_filtrar).'".') .
                                      '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

 <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const containerPedidos = document.getElementById('contenedor-pedidos');

                    if (containerPedidos) {
                        containerPedidos.addEventListener('click', function(e) {
                            const boton = e.target.closest('.btn-actualizar-estado');

                            if (boton) {
                                const idPedido = boton.dataset.idpedido;
                                const nuevoEstado = boton.dataset.nuevoEstado; // Puede ser 'En preparación' o 'Rechazado'

                                // Deshabilitar TODOS los botones de esa tarjeta para evitar doble clic
                                const botonesEnTarjeta = boton.parentElement.querySelectorAll('button');
                                botonesEnTarjeta.forEach(btn => btn.disabled = true);

                                boton.textContent = 'Procesando...';

                                const formData = new FormData();
                                formData.append('idpedido', idPedido);
                                formData.append('nuevo_estado', nuevoEstado);

                                fetch('actualizar_estado_pedido.php', {
                                        method: 'POST',
                                        body: formData
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            actualizarFila(idPedido, nuevoEstado);
                                            if (typeof window.consultarNotificaciones === 'function') {
                                                window.consultarNotificaciones();
                                            }
                                        } else {
                                            alert('Error: ' + data.message);
                                            boton.textContent = 'Reintentar';
                                            botonesEnTarjeta.forEach(btn => btn.disabled = false);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        alert('Error de conexión.');
                                        boton.textContent = 'Reintentar';
                                        botonesEnTarjeta.forEach(btn => btn.disabled = false);
                                    });
                            }
                        });
                    }
                });

                function actualizarFila(idPedido, nuevoEstado) {
                    const spanEstado = document.getElementById('status-' + idPedido);
                    const divAccion = document.getElementById('accion-' + idPedido);

                    if (!spanEstado || !divAccion) return;

                    // 1. Actualizar el Badge de estado
                    spanEstado.textContent = nuevoEstado;
                    spanEstado.className = 'order-status';

                    if (nuevoEstado === 'En preparación') spanEstado.classList.add('status-preparing');
                    else if (nuevoEstado === 'Listo') spanEstado.classList.add('status-ready');
                    else if (nuevoEstado === 'Entregado') spanEstado.classList.add('status-completed');
                    else if (nuevoEstado === 'Rechazado') spanEstado.classList.add('status-rechazado'); 
                    else if (nuevoEstado === 'Cancelado') spanEstado.classList.add('status-cancelado');
                    else spanEstado.classList.add('status-default');

                    // 2. Actualizar los botones (Siguiente paso)
                    let nuevoBotonHTML = '';

                    if (nuevoEstado === 'En preparación') {
                        nuevoBotonHTML = `<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${idPedido}' data-nuevo-estado='Listo'>Listo</button>`;
                    } else if (nuevoEstado === 'Listo') {
                        nuevoBotonHTML = `<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='${idPedido}' data-nuevo-estado='Entregado'>Entregado</button>`;
                    } else if (nuevoEstado === 'Entregado') {
                        nuevoBotonHTML = `<span style='color: #2ecc71; font-weight: bold;'>Completado</span>`;
                    } else if (nuevoEstado === 'Rechazado') {
                        nuevoBotonHTML = `<span style='color: #e74c3c; font-weight: bold;'>Rechazado</span>`;
                    } else if (nuevoEstado === 'Cancelado') {
                        nuevoBotonHTML = `<span style='color: #e74c3c; font-weight: bold;'>Cancelado</span>`;
                    }

                    divAccion.innerHTML = nuevoBotonHTML;
                }
                window.actualizarFila = actualizarFila;

                function filtrarPedidosEnPantalla() {
                    const selectFiltro = document.getElementById('filtro_estado');
                    const containerPedidos = document.getElementById('contenedor-pedidos');
                    if (!selectFiltro || !containerPedidos) return;

                    const estadoSel = selectFiltro.value;
                    const subHeader = document.querySelector('.welcome-header p');
                    if (subHeader) {
                        subHeader.innerHTML = (estadoSel === 'Todos')
                            ? 'Estás viendo: <strong>Todos los pedidos</strong>'
                            : 'Estás viendo los pedidos en estado: <strong>' + estadoSel + '</strong>';
                    }

                    const cards = containerPedidos.querySelectorAll('.order-card[data-idpedido]');
                    let visibles = 0;
                    cards.forEach(card => {
                        const badge = card.querySelector('.order-status');
                        const estCard = badge ? badge.textContent.trim() : '';
                        if (estadoSel === 'Todos' || estCard === estadoSel) {
                            card.style.display = '';
                            visibles++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                }

                document.addEventListener('DOMContentLoaded', function() {
                    const selectFiltro = document.getElementById('filtro_estado');
                    if (selectFiltro) {
                        selectFiltro.addEventListener('change', filtrarPedidosEnPantalla);
                    }
                    window.addEventListener('snackver:pedidos_actualizados', filtrarPedidosEnPantalla);
                });
            </script>


            <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>