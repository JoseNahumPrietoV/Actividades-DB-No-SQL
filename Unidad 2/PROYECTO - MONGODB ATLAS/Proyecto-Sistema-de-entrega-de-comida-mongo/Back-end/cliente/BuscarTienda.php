<?php
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

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
                        <a href="../cliente/dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="../cliente/MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="../cliente/BuscarTienda.php" class="active"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="../cliente/Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <?php
                // 1. OBTENER EL TÉRMINO DE BÚSQUEDA (MONGODB ATLAS)
                $termino_busqueda = trim($_GET['busqueda'] ?? '');
                $tiendas = [];

                try {
                    $mongoDb = MongoConexion::getConexion();
                    $colUsuarios = $mongoDb->selectCollection('usuarios');
                    $filtro = ['rol' => 'vendedor', 'estado' => 'activo'];

                    if (!empty($termino_busqueda)) {
                        $regex = new MongoDB\BSON\Regex($termino_busqueda, 'i');
                        $filtro['$or'] = [
                            ['tienda.nombre_negocio' => $regex],
                            ['tienda.descripcion' => $regex],
                            ['nombre' => $regex]
                        ];
                    }

                    $cursor = $colUsuarios->find($filtro, [
                        'projection' => [
                            'tienda'   => 1,
                            'nombre'   => 1,
                            'apellido' => 1,
                            'telefono' => 1
                        ]
                    ]);
                    foreach ($cursor as $doc) {
                        $tiendaData = $doc['tienda'] ?? [];
                        $tiendas[] = [
                            'idvendedor'        => (string)$doc['_id'],
                            'nombre_negocio'    => $tiendaData['nombre_negocio'] ?? ($doc['nombre'] . ' Store'),
                            'nombre_vendedor'   => $doc['nombre'] ?? '',
                            'apellido_vendedor' => $doc['apellido'] ?? '',
                            'telefono_vendedor' => $doc['telefono'] ?? '',
                            'descripcion'       => $tiendaData['descripcion'] ?? '',
                            'dirección'         => $tiendaData['direccion'] ?? ($tiendaData['dirección'] ?? ''),
                            'foto_negocio'      => $tiendaData['foto_negocio'] ?? ''
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Error buscando tiendas en MongoDB: " . $e->getMessage());
                }
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
                            if (!empty($tiendas)) {
                                foreach ($tiendas as $tienda) {
                                    $id_tienda = $tienda['idvendedor'];
                                    $nombre_tienda = htmlspecialchars($tienda['nombre_negocio'] ?? 'Nombre no disponible');
                                    $nombre_tienda_url = urlencode($nombre_tienda);
                                    $nombre_vendedor = htmlspecialchars($tienda['nombre_vendedor'] ?? '');
                                    $apellido_vendedor = htmlspecialchars($tienda['apellido_vendedor'] ?? '');
                                    $direccion = htmlspecialchars($tienda['direccion'] ?? ($tienda['dirección'] ?? ''));
                                    $descripcion = htmlspecialchars($tienda['descripcion'] ?? 'Descripción no disponible');
                                    $fotoTienda = !empty($tienda['foto_negocio']) ? $tienda['foto_negocio'] : '';
                                    if (empty($fotoTienda)) {
                                        $fotoTienda = "https://placehold.co/400x300/1A4F8B/ffffff?text=" . $nombre_tienda_url;
                                    }
                            ?>
                                    <div class="product-card">
                                        <div class="product-image" style="height: 150px; background-color: #f8fafc; display: flex; align-items: center; justify-content: center; overflow: hidden; border-top-left-radius: 8px; border-top-right-radius: 8px;">
                                            <img src="<?php echo htmlspecialchars($fotoTienda); ?>"
                                                alt="Foto de <?php echo $nombre_tienda; ?>"
                                                onerror="this.src='https://placehold.co/400x300/1A4F8B/ffffff?text=Tienda'"
                                                style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; object-position: center; padding: 4px;">
                                        </div>
                                        <div class="product-info">
                                            <!-- He quitado el ícono de estrella y la variable $calificacion que no estabas usando -->
                                            <h4><?php echo $nombre_tienda; ?></h4>
                                           <h4><?php echo $nombre_vendedor; ?> <?php echo $apellido_vendedor; ?></h4>

                                            <div class="store-stats" style="font-size: 0.9em; color: #555; margin-bottom: 5px;">
                                                <br>
                                                <?php echo $direccion; ?>
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
                            ?>

                        </div>
                    </div> <!-- Fin .dashboard-card -->
                </div> <!-- Fin .dashboard-content -->
            </div> <!-- Fin .dashboard-grid -->
        </div> <!-- Fin .container -->
    </main>



    <script src="../js/notificaciones.js?v=2.3"></script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formBusq = document.querySelector('.dashboard-card form');
            const inputBusq = document.querySelector('input[name="busqueda"]');
            if (formBusq) {
                formBusq.addEventListener('submit', function(e) {
                    e.preventDefault();
                    if (typeof window.sincronizarVistaTiempoReal === 'function') {
                        window.sincronizarVistaTiempoReal();
                    }
                });
            }
            if (inputBusq) {
                let timer = null;
                inputBusq.addEventListener('input', function() {
                    clearTimeout(timer);
                    timer = setTimeout(function() {
                        if (typeof window.sincronizarVistaTiempoReal === 'function') {
                            window.sincronizarVistaTiempoReal();
                        }
                    }, 200);
                });
            }
        });
    </script>
</body>

</html>