<?php

// 1. INCLUIR EL ARCHIVO DE CONEXIÓN
include '../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// 2. FILTROS
$filtro_estado = $_GET['estado'] ?? 'Todos';
$filtro_fecha = $_GET['fecha'] ?? '';

// 3. CONSTRUIR CONSULTA SQL
$sql = "SELECT p.idpedido, p.fechapedido, p.metodopago, v.nombre_vendedor, v.apellido_vendedor, v.nombre_negocio, p.total, p.estadopedido,
               GROUP_CONCAT(CONCAT(prod.nombreproducto , ' x', dp.cantidad) SEPARATOR ', ') AS lista_productos
        FROM pedido AS p
        JOIN vendedor AS v ON p.idvendedor = v.idvendedor
        LEFT JOIN detallepedido AS dp ON p.idpedido = dp.idpedido
        LEFT JOIN producto AS prod ON dp.idproducto = prod.idproducto
        WHERE p.idcliente = ?";

$conexion = $conn;
$where_condiciones = [];
$tipos_parametros = "i";
$valores_parametros = [$idClienteLogueado];

if ($filtro_estado != 'Todos' && $filtro_estado != '') {
    $where_condiciones[] = "p.estadopedido = ?";
    $tipos_parametros .= "s";
    $valores_parametros[] = $filtro_estado;
}

if (!empty($filtro_fecha)) {
    $where_condiciones[] = "DATE(p.fechapedido) = ?";
    $tipos_parametros .= "s";
    $valores_parametros[] = $filtro_fecha;
}

if (count($where_condiciones) > 0) {
    $sql .= " AND " . implode(" AND ", $where_condiciones);
}

$sql .= " GROUP BY p.idpedido, p.fechapedido, p.metodopago, v.nombre_vendedor, v.apellido_vendedor, v.nombre_negocio, p.total, p.estadopedido
          ORDER BY p.fechapedido DESC"; // Cambié a DESC para ver los nuevos primero

// 4. EJECUTAR CONSULTA
$stmt = $conexion->prepare($sql);
$stmt->bind_param($tipos_parametros, ...$valores_parametros);
$stmt->execute();
$resultado = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css">
    <link rel="stylesheet" href="../css/resumen-cliente.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
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
                        <div class="dropdown-header">Notificaciones</div>
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
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Cliente'); ?></h4>
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
                        if ($resultado->num_rows > 0) {
                            while ($fila = $resultado->fetch_assoc()) {
                                // Variables
                                $id_pedido_formato = "#ORD-" . $fila['idpedido'];
                                $fecha_formato = date("d M Y", strtotime($fila['fechapedido']));
                                $total_formato = "$" . number_format($fila['total'], 2);
                                $estado_pedido = htmlspecialchars($fila['estadopedido']);
                                $nombre_completo_vendedor = htmlspecialchars($fila['nombre_vendedor']) . ' ' . htmlspecialchars($fila['apellido_vendedor']);

                                // Clases CSS
                                $clase_estado = "status-pending";
                                if ($estado_pedido == "Entregado" || $estado_pedido == "Listo") $clase_estado = "status-completed";
                                if ($estado_pedido == "Cancelado" || $estado_pedido == "Rechazado") $clase_estado = "status-rejected";

                                $lista_productos = $fila['lista_productos'];
                        ?>

                                <div class="order-card">
                                    <div class="order-header">
                                        <div>
                                            <h4>Pedido <?php echo $id_pedido_formato; ?></h4>
                                            <p><?php echo $fecha_formato; ?></p>
                                        </div>
                                        <div>
                                            <span class="order-status <?php echo $clase_estado; ?>"><?php echo $estado_pedido; ?></span>
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

                                        <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: flex-end;">

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
                        $stmt->close();
                        $conexion->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lógica de notificaciones (la que ya tenías)
            const bellIcon = document.getElementById('bellIcon');
            const badge = document.getElementById('notification-badge');
            const dropdown = document.getElementById('notification-dropdown');
            const lista = document.getElementById('notification-list');

            if (bellIcon && dropdown) {
                bellIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
                    if (dropdown.style.display === 'block' && badge) badge.style.display = 'none';
                });
                document.addEventListener('click', function() {
                    dropdown.style.display = 'none';
                });
            }

            function consultarNotificaciones() {
                fetch('../cliente/verificar_notificaciones.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0 && lista) {
                            if (lista.querySelector('.no-notif')) lista.innerHTML = '';

                            // Limpiamos y llenamos para evitar duplicados en el polling
                            lista.innerHTML = '';

                            data.forEach(notif => {
                                const item = document.createElement('div');
                                item.className = 'notif-item';
                                let icono = '<i class="fas fa-info-circle" style="color:#3498db;"></i>';
                                if (notif.mensaje.includes('Listo')) icono = '<i class="fas fa-check-circle" style="color:#2ecc71;"></i>';
                                item.innerHTML = `${icono} <span>${notif.mensaje}</span>`;
                                lista.appendChild(item);
                            });
                            if (badge) badge.style.display = 'block';
                        }
                    })
                    .catch(error => console.log('Esperando...'));
            }
            setInterval(consultarNotificaciones, 5000);
        });

        // --- FUNCIÓN DE SIMULACIÓN DE PAGO (RUTA A) ---
        function simularPagoBanco(idPedido) {
            if (!confirm("🏦 BANCO DE PRUEBAS:\n\n¿Autorizar pago para el pedido #" + idPedido + "?\n(Esto cambiará el estado a 'En preparación' automáticamente)")) {
                return;
            }

            // Cambiamos el texto del botón temporalmente
            const btn = event.target.closest('button'); // Asegura agarrar el botón si clicaste el ícono
            const textoOriginal = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
            btn.disabled = true;

            // Ajusta la ruta ../../Back-end si tu estructura de carpetas es distinta
            fetch('../../Back-end/cliente/webhook_simulado.php?idpedido=' + idPedido)
                .then(response => response.text())
                .then(data => {
                    if (data.includes("Exito") || data.includes("actualizado") || data.includes("Pago Confirmado")) {
                        alert("✅ ¡PAGO APROBADO!\n\nEl sistema bancario ha confirmado la transferencia.");
                        location.reload();
                    } else {
                        alert("⚠️ Resultado del simulador:\n" + data); // Muestra el error si lo hay
                        btn.innerHTML = textoOriginal;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("❌ Error: No se encontró el archivo 'webhook_simulado.php'. Revisa la ruta.");
                    btn.innerHTML = textoOriginal;
                    btn.disabled = false;
                });
        }
    </script>
</body>

</html>