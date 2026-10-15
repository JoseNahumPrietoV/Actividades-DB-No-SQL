<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// --- 2. RECONECTAR A LA BASE DE DATOS ---
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtenemos el ID del vendedor
$id_vendedor = $_SESSION['id_vendedor'];

// --- 3. LÓGICA DEL FILTRO ---
// Si viene en la URL (?filtro_estado=X) úsalo, si no, usa 'Pendiente' por defecto
$estado_filtrar = isset($_GET['filtro_estado']) ? $_GET['filtro_estado'] : 'Pendiente';

// --- 4. CONSULTA PRINCIPAL DE PEDIDOS ---
$sql_tabla = "SELECT
                p.idpedido,
                p.fechapedido,
                p.referenciapago,
                p.metodopago,
                CONCAT(c.nombre_cliente, ' ', c.apellido_cliente) AS nombre_completo_cliente,
                p.total,
                p.estadopedido,
                GROUP_CONCAT(CONCAT(prod.nombreproducto, ' x', dp.cantidad) SEPARATOR ', ') AS lista_productos
            FROM
                pedido AS p
            JOIN
                cliente AS c ON p.idcliente = c.idCliente
            LEFT JOIN
                detallepedido AS dp ON p.idpedido = dp.idpedido
            LEFT JOIN
                producto AS prod ON dp.idproducto = prod.idproducto
            WHERE
                p.idvendedor = ? 
                AND p.estadopedido = ?  /* <--- AQUÍ APLICAMOS EL FILTRO */
            GROUP BY
                p.idpedido, p.total, p.estadopedido, nombre_completo_cliente
            ORDER BY
                p.fechapedido DESC";

$stmt = $conn->prepare($sql_tabla);
$stmt->bind_param("is", $id_vendedor, $estado_filtrar); // 'i' para ID, 's' para el estado
$stmt->execute();
$resultado_pedidos = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Vendedor - Snackver</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-vendedor.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/resumen-vendedor.css?v=<?php echo time(); ?>">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>

    <header>
        <div class="container header-content">
            <a href="../../Back-end/vendedor/dashboard_vendedor.php" class="logo">
                <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <nav></nav>
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
                if ($resultado_pedidos && $resultado_pedidos->num_rows > 0) {
                    while ($fila = $resultado_pedidos->fetch_assoc()) {
                        // Variables
                        $id_pedido = $fila['idpedido'];
                        $id_pedido_texto = "#ORD-" . htmlspecialchars($id_pedido);
                        $fecha = date("d/m/Y h:i A", strtotime($fila['fechapedido']));
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

                                <div class="order-card">
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
                    }

                    divAccion.innerHTML = nuevoBotonHTML;
                }
            </script>


            <script>
                document.addEventListener('DOMContentLoaded', function() {

                    // 1. Referencias a los elementos
                    const bellIcon = document.getElementById('bellIcon');
                    const badge = document.getElementById('notification-badge');
                    const dropdown = document.getElementById('notification-dropdown');
                    const lista = document.getElementById('notification-list');

                    // 2. Lógica del menú desplegable
                    if (bellIcon && dropdown) {
                        bellIcon.addEventListener('click', function(e) {
                            e.stopPropagation();
                            // Alternar visibilidad
                            if (dropdown.style.display === 'block') {
                                dropdown.style.display = 'none';
                            } else {
                                dropdown.style.display = 'block';
                                // Ocultar la burbuja roja al abrir
                                if (badge) badge.style.display = 'none';
                            }
                        });

                        // Cerrar si se hace clic fuera
                        document.addEventListener('click', function() {
                            dropdown.style.display = 'none';
                        });
                    }

                    // 3. Función de consulta (Limpia)
                    function consultarNotificaciones() {
                        const timestamp = new Date().getTime(); // Evitar caché

                        // Usamos la ruta relativa directa que te funcionó
                        fetch('verificar_notificaciones_vendedor.php?t=' + timestamp)
                            .then(response => {
                                if (!response.ok) {
                                    // Silencioso: si falla la red, simplemente no hacemos nada
                                    return null;
                                }
                                return response.json();
                            })
                            .then(data => {
                                // Si data es null o no es array, salimos
                                if (!data || !Array.isArray(data)) return;

                                if (data.length > 0) {

                                    // A. Mostrar puntito rojo
                                    if (badge) {
                                        badge.style.display = 'block';
                                        badge.textContent = data.length;
                                    }

                                    // B. Llenar la lista
                                    if (lista) {
                                        lista.innerHTML = ''; // Limpiar lista anterior

                                        data.forEach(notif => {
                                            const item = document.createElement('div');
                                            item.className = 'notif-item';

                                            // Elegir color del icono según el tipo de mensaje
                                            let colorIcono = '#f39c12'; // Naranja por defecto
                                            let claseIcono = 'fa-bell';

                                            if (notif.mensaje.includes('cancelado')) {
                                                colorIcono = '#e74c3c'; // Rojo
                                                claseIcono = 'fa-exclamation-triangle';
                                            } else if (notif.mensaje.includes('pedido')) {
                                                colorIcono = '#2ecc71'; // Verde
                                                claseIcono = 'fa-receipt';
                                            }

                                            item.innerHTML = `
                                    <a href="../vendedor/Gestion_platillos.php" style="text-decoration:none; color:#333; display:flex; align-items:center; gap:10px; padding:10px; border-bottom:1px solid #eee;">
                                        <i class="fas ${claseIcono}" style="color:${colorIcono};"></i> 
                                        <span style="font-size:13px;">${notif.mensaje}</span>
                                    </a>
                                `;
                                            lista.appendChild(item);
                                        });
                                    }
                                }
                            })
                            .catch(error => {
                                // Dejamos solo errores críticos en consola, pero quitamos el spam
                                // console.error(error); 
                            });
                    }

                    // Ejecutar cada 2 segundos
                    setInterval(consultarNotificaciones, 2000);

                    // Ejecutar inmediatamente al cargar
                    consultarNotificaciones();
                });
            </script>



</body>

</html>