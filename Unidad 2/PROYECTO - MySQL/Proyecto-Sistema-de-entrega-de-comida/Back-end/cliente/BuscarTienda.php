<?php
// 💡 1. INCLUIR EL ARCHIVO CON EL CÓDIGO DE CONEXIÓN Y CONSULTA
include '../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);
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
                <h1>Tu Cuenta <?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre del Cliente'); ?> <?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? ''); ?></h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre del Cliente'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? 'Apellido del cliente'); ?></p>
                    </div>
                    <nav class="sidebar-nav">
                        <a href="../cliente/dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php" class="active"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php""><i class=" fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <?php
                // 1. OBTENER EL TÉRMINO DE BÚSQUEDA (si existe)
                $termino_busqueda = "";
                if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
                    $termino_busqueda = $_GET['busqueda'];
                }

                // 2. CONSTRUIR LA CONSULTA SQL
                $sql_tiendas = "SELECT 
                        idvendedor, 
                        nombre_negocio, 
                        nombre_vendedor,
                        apellido_vendedor,
                        telefono_vendedor,
                        descripcion,
                        dirección
                    FROM vendedor";
                if (!empty($termino_busqueda)) {
                    $termino_like = "%" . $termino_busqueda . "%";
                    $sql_tiendas .= " WHERE nombre_negocio LIKE ? OR descripcion LIKE ?";
                    $stmt = $conn->prepare($sql_tiendas);
                    $stmt->bind_param("ss", $termino_like, $termino_like);
                } else {
                    $stmt = $conn->prepare($sql_tiendas);
                }

                $stmt->execute();
                $resultado_tiendas = $stmt->get_result();
                ?>

                <div class="dashboard-content">
                    <div class="dashboard-card">
                        <h3>Buscar Tiendas</h3>
                        <form action="" method="GET" style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
                            <input type="text"
                                name="busqueda"
                                placeholder="Buscar por nombre o tipo de comida (ej: pizza, tacos)"
                                style="flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px;"
                                value="<?php echo htmlspecialchars($termino_busqueda); ?>">
                            <button type="submit" class="btn btn-primary" style="height: 42px; display: flex; align-items: center;">
                                <i class="fas fa-filter" style="margin-right: 5px;"></i> Buscar Tienda
                            </button>
                        </form>

                        <div class="products-grid">

                            <?php
                            if ($resultado_tiendas->num_rows > 0) {
                                while ($tienda = $resultado_tiendas->fetch_assoc()) {
                                    $id_tienda = intval($tienda['idvendedor']);
                                    $nombre_tienda = htmlspecialchars($tienda['nombre_negocio'] ?? 'Nombre no disponible');
                                    // Preparamos el nombre para la URL del placeholder
                                    $nombre_tienda_url = urlencode($nombre_tienda);
                                    $nombre_vendedor = htmlspecialchars($tienda['nombre_vendedor'] ?? '0');
                                    $apellido_vendedor = htmlspecialchars($tienda['apellido_vendedor'] ?? '0');
                                    $dirección = htmlspecialchars($tienda['dirección'] ?? '0');
                                    $descripcion = htmlspecialchars($tienda['descripcion'] ?? 'Descripción no disponible');
                            ?>
                                    <div class="product-card">
                                        <div class="product-image">

                                            <!-- ===== ¡AQUÍ ESTÁ LA IMAGEN GENÉRICA! ===== -->
                                            <img src="https://placehold.co/400x300/eee/999?text=<?php echo $nombre_tienda_url; ?>"
                                                alt="Foto de <?php echo $nombre_tienda; ?>"
                                                style="width: 100%; height: 120px; object-fit: cover; border-radius: 6px;">

                                        </div>
                                        <div class="product-info">
                                            <!-- He quitado el ícono de estrella y la variable $calificacion que no estabas usando -->
                                            <h4><?php echo $nombre_tienda; ?></h4>
                                           <h4><?php echo $nombre_vendedor; ?> <?php echo $apellido_vendedor; ?></h4>

                                            <div class="store-stats" style="font-size: 0.9em; color: #555; margin-bottom: 5px;">
                                                <br>
                                                <?php echo $dirección; ?>
                                            </div>
                                            <p><?php echo $descripcion; ?></p>

                                            <div class="product-actions" style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                                                <a href="ver_tienda.php?id=<?php echo $id_tienda; ?>" class="btn btn-primary">Ver Tienda</a>
                                            </div>
                                        </div>
                                    </div>

                            <?php
                                } // Fin del while
                            } else {
                                echo "<p>No se encontraron tiendas...</p>";
                            }
                            $stmt->close();
                            ?>

                        </div>
                    </div> <!-- Fin .dashboard-card -->
                </div> <!-- Fin .dashboard-content -->
            </div> <!-- Fin .dashboard-grid -->
        </div> <!-- Fin .container -->
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