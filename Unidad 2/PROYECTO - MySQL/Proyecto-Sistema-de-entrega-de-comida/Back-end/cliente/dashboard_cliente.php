<?php

// Back-end/cliente/verificar_notificaciones.php
include '../conexion/pantalla_usuario_cliente.php'; // Tu conexión


// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? ''; // Mantengo 'mensaje_perfil' si ya lo usas para mostrar el resultado
unset($_SESSION['mensaje_perfil']); // Limpiar la variable después de mostrar
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cliente - Snackver</title>
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
                <h1>Tu Cuenta <?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre del Cliente'); ?>
                    <?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? 'Apellido del cliente'); ?></h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre del Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? 'Apellido del cliente'); ?></p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php" class="active"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php""><i class=" fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card">
                   
                        <h3>Pedidos Recientes</h3>

                        <?php
                        // --- 1. CONEXIÓN A LA BASE DE DATOS ---
                        $servidor = "127.0.0.1";
                        $usuario = "root";
                        $clave = "2884";
                        $db = "sistemawebsnackver";

                        $conexion = new mysqli($servidor, $usuario, $clave, $db);
                        if ($conexion->connect_error) {
                            die("Conexión fallida: " . $conexion->connect_error);
                        }

                        // --- 2. DEFINIR FILTROS FIJOS ---
                        $idClienteLogueado = $_SESSION['id_cliente'];
                        $estado_fijo = "En Preparación"; // <-- FILTRO FIJO

                        // --- 3. CONSTRUIR CONSULTA SQL FIJA ---
                        $sql = "SELECT
                                    p.idpedido, p.fechapedido, v.nombre_vendedor,v.apellido_vendedor, v.nombre_negocio, p.total, p.estadopedido,
                                    GROUP_CONCAT(CONCAT(prod.nombreproducto , ' x', dp.cantidad) SEPARATOR ', ') AS lista_productos
                                FROM
                                    pedido AS p
                                JOIN
                                    vendedor AS v ON p.idvendedor = v.idvendedor
                                LEFT JOIN
                                    detallepedido AS dp ON p.idpedido = dp.idpedido
                                LEFT JOIN
                                    producto AS prod ON dp.idproducto = prod.idproducto
                                WHERE 
                                    p.idcliente = ? 
                                    AND p.estadopedido = ? "; // <-- CONDICIÓN FIJA AGREGADA

                        // --- Terminar la consulta ---
                        $sql .= " GROUP BY p.idpedido, p.fechapedido, v.nombre_vendedor, v.nombre_negocio, p.total, p.estadopedido
                                ORDER BY p.fechapedido ASC";

                        // --- 4. PREPARAR Y EJECUTAR CONSULTA ---
                        $stmt = $conexion->prepare($sql);
                        // "i" por el idCliente (integer), "s" por el estado (string)
                        $stmt->bind_param("is", $idClienteLogueado, $estado_fijo);
                        $stmt->execute();
                        $resultado = $stmt->get_result(); // ¡Ahora $resultado tiene los datos FILTRADOS!

                        ?>

                        <div class="order-history">
                            <?php
                            // --- 5. GENERAR TARJETAS DE PEDIDO ---
                            if ($resultado->num_rows > 0) {
                                while ($fila = $resultado->fetch_assoc()) {

                                    // Formatear datos
                                    $id_pedido_formato = "#ORD-" . $fila['idpedido'];
                                    $fecha_formato = date("d M Y", strtotime($fila['fechapedido']));
                                    $total_formato = "$" . number_format($fila['total'], 2);
                                    $estado_pedido = htmlspecialchars($fila['estadopedido']);
                                    $nombre_vendedor = htmlspecialchars($fila['nombre_vendedor']);
                                    $apellido_vendedor = htmlspecialchars($fila['apellido_vendedor']);

                                    // Lógica para la clase CSS del estado
                                    $clase_estado = "status-pending";
                                    if ($estado_pedido == "Entregado" || $estado_pedido == "Listo") $clase_estado = "status-completed";
                                    if ($estado_pedido == "Cancelado" || $estado_pedido == "Rechazado") $clase_estado = "status-rejected";

                                    $productos_raw = $fila['lista_productos'];

                            ?>

                                    <div class="order-card">
                                        <div class="order-header">
                                            <div>
                                                <h4>Pedido <?php echo htmlspecialchars($id_pedido_formato); ?></h4>
                                                <p>Realizado el <?php echo htmlspecialchars($fecha_formato); ?></p>
                                            </div>
                                            <div>
                                                <span class="order-status <?php echo $clase_estado; ?>"><?php echo $estado_pedido; ?></span>
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
                                        <div class="order-item order-item-details" style="padding-top: 10px;">


                                            <span class="order-total-inline"><strong>Total: <?php echo $total_formato; ?></strong></span>

                                        </div>
                                        <div class="order-footer">

                                            <div>
                                                <?php if ($estado_pedido == 'Pendiente'): ?>
                                                    <form action="cancelar_pedido_cliente.php" method="POST" style="display:inline;">
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

                                    // --- 6. CERRAR CONEXIÓN ---
                                    $stmt->close();
                                    $conexion->close();
                                            ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. Obtener elementos (Puede que bellIcon no exista si no copiaste el HTML del paso 1)
            const bellIcon = document.getElementById('bellIcon');
            const badge = document.getElementById('notification-badge');
            const dropdown = document.getElementById('notification-dropdown');
            const lista = document.getElementById('notification-list');

            // Solo ejecutar si la campana existe en la página
            if (bellIcon && dropdown) {

                // Toggle del menú
                bellIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (dropdown.style.display === 'block') {
                        dropdown.style.display = 'none';
                    } else {
                        dropdown.style.display = 'block';
                        // Ocultar puntito rojo al abrir
                        if (badge) badge.style.display = 'none';
                    }
                });

                // Cerrar al hacer clic fuera
                document.addEventListener('click', function() {
                    dropdown.style.display = 'none';
                });
            }

            // Función de consulta (Polling)
            function consultarNotificaciones() {
                // Ajusta la ruta si es necesario
                fetch('../cliente/verificar_notificaciones.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0 && lista) {

                            // Limpiar mensaje de "No hay notificaciones"
                            if (lista.querySelector('.no-notif')) {
                                lista.innerHTML = '';
                            }

                            data.forEach(notif => {
                                const item = document.createElement('div');
                                item.className = 'notif-item';
                                // Icono según mensaje (opcional, aquí pongo uno genérico)
                                let icono = '<i class="fas fa-info-circle" style="color:#3498db;"></i>';
                                if (notif.mensaje.includes('Listo')) icono = '<i class="fas fa-check-circle" style="color:#2ecc71;"></i>';

                                item.innerHTML = `${icono} <span>${notif.mensaje}</span>`;

                                // Agregar al inicio
                                lista.insertBefore(item, lista.firstChild);
                            });

                            // Mostrar puntito rojo
                            if (badge) badge.style.display = 'block';
                        }
                    })
                    .catch(error => console.log('Esperando notificaciones...'));
            }

            // Iniciar sondeo cada 5 seg
            setInterval(consultarNotificaciones, 5000);
        });
    </script>
</body>

</html>