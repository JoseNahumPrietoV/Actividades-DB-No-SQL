<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
include '../conexion/pantalla_usuario_vendedor.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// --- 2. RECONECTAR A LA BASE DE DATOS ---
// Necesario porque el include anterior cierra la conexión
$host = "127.0.0.1";
$db_user = "root";
$db_pass = "2884";
$db_name = "sistemawebsnackver";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtenemos el ID del vendedor de la sesión
$id_vendedor_logueado = $_SESSION['id_vendedor'];

// --- 3. CONSULTAS PARA LAS TARJETAS DE RESUMEN ---

// Tarjeta 1: Pedidos Nuevos (Pendientes de hoy)
$sql_nuevos = "SELECT COUNT(idpedido) AS total
               FROM pedido 
               WHERE idvendedor = ? 
               AND estadopedido = 'Pendiente' 
               AND DATE(fechapedido) = CURDATE()";
$stmt_nuevos = $conn->prepare($sql_nuevos);
$stmt_nuevos->bind_param("i", $id_vendedor_logueado);
$stmt_nuevos->execute();
$total_pedidos_nuevos = $stmt_nuevos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_nuevos->close();

// Tarjeta 2: Ingresos del Día (Entregados hoy)
$sql_ingresos = "SELECT SUM(total) AS total
                 FROM pedido 
                 WHERE idvendedor = ? 
                 AND estadopedido = 'Entregado' 
                 AND DATE(fechapedido) = CURDATE()";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("i", $id_vendedor_logueado);
$stmt_ingresos->execute();
$total_ingresos_hoy = $stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0.00;
$stmt_ingresos->close();

// Tarjeta 3: Pedidos Pendientes (Total acumulado)
$sql_pendientes = "SELECT COUNT(idpedido) AS total
                   FROM pedido 
                   WHERE idvendedor = ? 
                   AND estadopedido = 'Pendiente'";
$stmt_pendientes = $conn->prepare($sql_pendientes);
$stmt_pendientes->bind_param("i", $id_vendedor_logueado);
$stmt_pendientes->execute();
$total_pendientes = $stmt_pendientes->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_pendientes->close();

// Tarjeta 4: Valoración Media
$sql_rating = "SELECT AVG(r.calificacion) AS media
               FROM resena AS r
               JOIN producto AS prod ON r.idproducto = prod.idproducto
               WHERE prod.idvendedor = ?";
$stmt_rating = $conn->prepare($sql_rating);
$stmt_rating->bind_param("i", $id_vendedor_logueado);
$stmt_rating->execute();
$valoracion_media = $stmt_rating->get_result()->fetch_assoc()['media'] ?? 0;
$stmt_rating->close();

// Variables globales para las tarjetas del dashboard (tomadas de tus consultas anteriores)
$total_general = $total_pendientes; // Asumo que esta variable la usabas para pendientes o total general
// Si necesitas el total histórico (sin filtro de estado):
$sql_total_hist = "SELECT COUNT(*) as total FROM pedido WHERE idvendedor = ?";
$stmt_hist = $conn->prepare($sql_total_hist);
$stmt_hist->bind_param("i", $id_vendedor_logueado);
$stmt_hist->execute();
$total_general = $stmt_hist->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_hist->close();

// Total Productos
$sql_prod = "SELECT COUNT(*) as total FROM producto WHERE idvendedor = ?";
$stmt_prod = $conn->prepare($sql_prod);
$stmt_prod->bind_param("i", $id_vendedor_logueado);
$stmt_prod->execute();
$total_productos = $stmt_prod->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_prod->close();





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
            <h1>Panel de Vendedor <?php echo htmlspecialchars($datos_vendedor['nombre_vendedor'] ?? 'Nombre'); ?> <?php echo htmlspecialchars($datos_vendedor['apellido_vendedor'] ?? 'Apellido'); ?> </h1>
            <p>Gestiona tus pedidos, platillos y revisa tus ganancias del día.</p>
        </div>
    </main>



    <main class="dashboard-main-content">
        <div class="container">
            <?php if (!empty($mensaje_perfil)): ?>
                <div class="alert" style="color: white; background-color: #4CAF50; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($mensaje_perfil); ?>
                </div>
            <?php endif; ?>



            <div class="dashboard-grid">

                <div class="dashboard-content">

                    <a href="../vendedor/pedidos_pendientes.php?filtro_estado=Pendiente" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Pedidos Recientes</div>
                        <div class="card-body">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-footer">
                            <?php echo $total_pendientes; ?> Pendientes
                        </div>
                    </a>

                    <a href="../vendedor/Gestion_platillos.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Gestionar Pedidos</div>
                        <div class="card-body">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="card-footer">
                            <?php echo $total_general; ?> Registrados
                        </div>
                    </a>

                    <a href="../vendedor/Misplatillos.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Productos</div>
                        <div class="card-body">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="card-footer">
                            <?php echo $total_productos; ?> Registrados
                        </div>
                    </a>

                    <a href="../vendedor/MiTienda.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Mi tienda</div>
                        <div class="card-body">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="card-footer">Configurar</div>
                    </a>

                </div>


            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // 1. Referencias
            const bellIcon = document.getElementById('bellIcon');
            const badge = document.getElementById('notification-badge');
            const dropdown = document.getElementById('notification-dropdown');
            const lista = document.getElementById('notification-list');

            // 2. Menú desplegable
            if (bellIcon && dropdown) {
                bellIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (dropdown.style.display === 'block') {
                        dropdown.style.display = 'none';
                    } else {
                        dropdown.style.display = 'block';
                        if (badge) badge.style.display = 'none';
                    }
                });
                document.addEventListener('click', function() {
                    dropdown.style.display = 'none';
                });
            }

            // 3. Consulta de Notificaciones
            function consultarNotificaciones() {
                const timestamp = new Date().getTime();

                fetch('verificar_notificaciones_vendedor.php?t=' + timestamp)
                    .then(response => {
                        if (!response.ok) return null;
                        return response.json();
                    })
                    .then(data => {
                        if (!data || !Array.isArray(data)) return;

                        if (data.length > 0) {
                            // Mostrar puntito rojo
                            if (badge) {
                                badge.style.display = 'block';
                                badge.textContent = data.length;
                            }

                            // Llenar lista
                            if (lista) {
                                lista.innerHTML = '';

                                data.forEach(notif => {
                                    const item = document.createElement('div');
                                    item.className = 'notif-item';

                                    // --- LÓGICA DE REDIRECCIÓN Y COLORES ---

                                    // 1. Definimos la URL base
                                    let urlDestino = '../vendedor/Gestion_platillos.php';

                                    // 2. Filtro según tipo de notificación
                                    if (notif.tipo === 'En preparación') {
                                        urlDestino += '?filtro_estado=En preparación';
                                    } else if (notif.tipo === 'Cancelado') {
                                        urlDestino += '?filtro_estado=Cancelado';
                                    } else {
                                        // Para nuevos pedidos (Pendientes)
                                        urlDestino += '?filtro_estado=Pendiente';
                                    }

                                    // 3. Iconos y colores
                                    let colorIcono = '#f39c12';
                                    let claseIcono = 'fa-bell';

                                    if (notif.mensaje.includes('cancelado')) {
                                        colorIcono = '#e74c3c';
                                        claseIcono = 'fa-exclamation-triangle';
                                    } else if (notif.mensaje.includes('Pago confirmado')) {
                                        colorIcono = '#2ecc71';
                                        claseIcono = 'fa-money-bill-wave';
                                    } else if (notif.mensaje.includes('pedido')) {
                                        colorIcono = '#3498db';
                                        claseIcono = 'fa-receipt';
                                    }

                                    // 4. Generamos el HTML
                                    item.innerHTML = `
                                        <a href="${urlDestino}" style="text-decoration:none; color:#333; display:flex; align-items:center; gap:10px; padding:10px; border-bottom:1px solid #eee;">
                                            <i class="fas ${claseIcono}" style="color:${colorIcono};"></i> 
                                            <span style="font-size:13px;">${notif.mensaje}</span>
                                        </a>
                                    `;

                                    lista.appendChild(item);
                                });
                            }
                        }
                    })
                    .catch(error => {});
            }

            setInterval(consultarNotificaciones, 2000);
            consultarNotificaciones();
        });
    </script>

</body>

</html>