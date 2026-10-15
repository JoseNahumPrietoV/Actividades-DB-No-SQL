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
$estado_filtrar = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : 'Pendiente';

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

    $filtro = [
        '$and' => [
            $filtroVendedor,
            ['estadopedido' => $estado_filtrar]
        ]
    ];

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
            $fechaStr = date('d/m/Y h:i A', $p['fechapedido']->toDateTime()->getTimestamp());
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
    error_log("Error cargando pedidos en Gestion_platillos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor - Snackver</title>
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
                <h1>Gestionar Pedidos</h1>
                <p>Estás viendo los pedidos en estado: <strong><?php echo htmlspecialchars($estado_filtrar); ?></strong></p>
            </div>

            <div class="filter-section">
                <a href="dashboard_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>

                <form method="GET" action="" class="filter-form">
                    <label for="filtro_estado">Filtrar por:</label>
                    <select name="filtro_estado" id="filtro_estado" class="filter-select" onchange="this.form.submit()">
                        <option value="Pendiente" <?php if($estado_filtrar == 'Pendiente') echo 'selected'; ?>>🟡 Pendientes (Nuevos)</option>
                        <option value="En preparación" <?php if($estado_filtrar == 'En preparación') echo 'selected'; ?>>🔵 En preparación (Pagados)</option>
                        <option value="Listo" <?php if($estado_filtrar == 'Listo') echo 'selected'; ?>>🟢 Listos para entregar</option>
                        <option value="Entregado" <?php if($estado_filtrar == 'Entregado') echo 'selected'; ?>>🏁 Entregados (Historial)</option>
                        <option value="Cancelado" <?php if($estado_filtrar == 'Cancelado') echo 'selected'; ?>>🔴 Cancelados</option>
                        <option value="Rechazado" <?php if($estado_filtrar == 'Rechazado') echo 'selected'; ?>>🔴 Rechazados</option>
                    </select>
                </form>
            </div>


                    <div id="contenedor-pedidos" class="order-history">
                <?php
                if (!empty($pedidos_vendedor)) {
                    foreach ($pedidos_vendedor as $fila) {
                        // Variables
                        $id_pedido = $fila['idpedido'];
                        $id_pedido_texto = "#ORD-" . htmlspecialchars($id_pedido);
                        $fecha = $fila['fechapedido_formateada'];
                        $cliente = htmlspecialchars($fila['nombre_completo_cliente']);
                        $productos = htmlspecialchars($fila['lista_productos']);
                        $total = "$" . number_format($fila['total'], 2);
                        $estado = htmlspecialchars($fila['estadopedido']);
                        $referenciapago = htmlspecialchars($fila['referenciapago']);
                        $metodopago = htmlspecialchars($fila['metodopago']);

                        // Clases CSS
                        $clase_estado = 'status-default';
                        if ($estado == 'Pendiente') $clase_estado = 'status-pending';
                        if ($estado == 'En preparación') $clase_estado = 'status-preparing';
                        if ($estado == 'Listo') $clase_estado = 'status-ready';
                        if ($estado == 'Entregado') $clase_estado = 'status-delivered';
                        if ($estado == 'Cancelado') $clase_estado = 'status-cancelado';
                        if ($estado == 'Rechazado') $clase_estado = 'status-rechazado';
                ?>

                                <div class="order-card" data-idpedido="<?php echo $id_pedido; ?>">
                                    <div class="order-header">
                                        <div>
                                            <h4>Pedido <?php echo $id_pedido_texto; ?></h4>
                                            <p>Realizado el <?php echo $fecha; ?></p>
                                        </div>
                                        <div>
                                            <span id="status-<?php echo $id_pedido; ?>" class="order-status <?php echo $clase_estado; ?>">
                                                <?php echo $estado; ?>
                                            </span>
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
                                            <span><strong>Metodo de Pago:</strong> <?php echo $metodopago; ?></span>
                                        </div>
                                        <div class="order-item order-item-details">
                                            <span><strong>Referencia:</strong> <?php echo $referenciapago; ?></span>
                                        </div>
                                    </div>

                                    <div class="order-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                        <div class="order-total-inline">
                                            <strong>Total: <?php echo $total; ?></strong>
                                        </div>

                                        <div id="accion-<?php echo $id_pedido; ?>" style="display: flex; gap: 10px;">
                                            <?php
                                            switch ($estado) {
                                                case 'Pendiente':
                                                    // AQUÍ AGREGAMOS EL BOTÓN DE RECHAZAR
                                                    echo "<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='$id_pedido' data-nuevo-estado='En preparación'>Aceptar</button>";
                                                    echo "<button class='btn btn-danger btn-sm btn-actualizar-estado' data-idpedido='$id_pedido' data-nuevo-estado='Rechazado'>Rechazar</button>";
                                                    break;

                                                case 'En preparación':
                                                    echo "<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='$id_pedido' data-nuevo-estado='Listo'>Listo</button>";
                                                    break;

                                                case 'Listo':
                                                    echo "<button class='btn btn-success btn-sm btn-actualizar-estado' data-idpedido='$id_pedido' data-nuevo-estado='Entregado'>Entregado</button>";
                                                    break;

                                                case 'Entregado':
                                                    echo "<span style='color: #2ecc71; font-weight: bold;'>Completado</span>";
                                                    break;

                                                case 'Cancelado':
                                                    echo "<span style='color: #e74c3c; font-weight: bold;'>Cancelado</span>";
                                                    break;

                                                case 'Rechazado':
                                                    echo "<span style='color: #e74c3c; font-weight: bold;'>Rechazado</span>";
                                                    break;
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo '<div class="order-card" style="text-align: center; color: #888; padding: 20px;">No se encontraron pedidos con este filtro.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

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
            </script>


            <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>