<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// Obtenemos el ID del vendedor de la sesión
$id_vendedor_logueado = $_SESSION['id_vendedor'];

// Liberar candado de sesión inmediatamente para permitir recargas fluidas
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 2. CONSULTAS PARA LAS TARJETAS DE RESUMEN (MONGODB ATLAS) ---
$total_pedidos_nuevos = 0;
$total_ingresos_hoy   = 0.00;
$total_pendientes     = 0;
$valoracion_media     = 0.0;
$total_general        = 0;
$total_productos      = 0;

try {
    $mongoDb = MongoConexion::getConexion();
    $colPedidos   = $mongoDb->selectCollection('pedidos');
    $colProductos = $mongoDb->selectCollection('productos');
    $colResenas   = $mongoDb->selectCollection('resenas');

    // Filtro para el ID de vendedor (ObjectId, string o int)
    $filtroVendedor = [];
    if (is_string($id_vendedor_logueado) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor_logueado)) {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => new MongoDB\BSON\ObjectId($id_vendedor_logueado)],
            ['id_vendedor' => (string)$id_vendedor_logueado],
            ['id_vendedor' => (int)$id_vendedor_logueado]
        ]];
    } else {
        $filtroVendedor = ['$or' => [
            ['id_vendedor' => (int)$id_vendedor_logueado],
            ['id_vendedor' => (string)$id_vendedor_logueado]
        ]];
    }

    $hoyInicio = new MongoDB\BSON\UTCDateTime(strtotime('today midnight') * 1000);

    // Consulta unificada de alto rendimiento: combina 4 métricas en un solo round-trip con $facet
    $pipePedidos = [
        ['$match' => $filtroVendedor],
        ['$facet' => [
            'pedidos_nuevos' => [
                ['$match' => ['estadopedido' => 'Pendiente', 'fechapedido' => ['$gte' => $hoyInicio]]],
                ['$count' => 'total']
            ],
            'ingresos_hoy' => [
                ['$match' => ['estadopedido' => 'Entregado', 'fechapedido' => ['$gte' => $hoyInicio]]],
                ['$group' => ['_id' => null, 'total' => ['$sum' => '$total']]]
            ],
            'pendientes' => [
                ['$match' => ['estadopedido' => 'Pendiente']],
                ['$count' => 'total']
            ],
            'total_general' => [
                ['$count' => 'total']
            ]
        ]]
    ];

    $resPedidos = $colPedidos->aggregate($pipePedidos)->toArray();
    if (!empty($resPedidos[0])) {
        $f = $resPedidos[0];
        $total_pedidos_nuevos = !empty($f['pedidos_nuevos'][0]['total']) ? (int)$f['pedidos_nuevos'][0]['total'] : 0;
        $total_ingresos_hoy   = !empty($f['ingresos_hoy'][0]['total']) ? (float)$f['ingresos_hoy'][0]['total'] : 0.00;
        $total_pendientes     = !empty($f['pendientes'][0]['total']) ? (int)$f['pendientes'][0]['total'] : 0;
        $total_general        = !empty($f['total_general'][0]['total']) ? (int)$f['total_general'][0]['total'] : 0;
    }

    // Tarjeta 4: Valoración Media
    $pipeRating = [
        ['$group' => [
            '_id' => null,
            'media' => ['$avg' => '$calificacion']
        ]]
    ];
    $cursorRating = $colResenas->aggregate($pipeRating);
    foreach ($cursorRating as $r) {
        $valoracion_media = round((float)($r['media'] ?? 0), 1);
    }

    // Total Productos
    $total_productos = $colProductos->countDocuments($filtroVendedor);

} catch (Exception $e) {
    error_log("Error calculando estadísticas en dashboard_vendedor: " . $e->getMessage());
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
            <h1>Panel de Vendedor &mdash; <?php echo htmlspecialchars($datos_vendedor['nombre_completo'] ?? 'Vendedor'); ?></h1>
            <p><span style="background-color: #1A4F8B; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.82rem; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;"><i class="fas fa-store"></i> <?php echo htmlspecialchars($datos_vendedor['rol_label'] ?? 'Vendedor'); ?></span> &mdash; Gestiona tus pedidos, platillos y revisa tus ganancias del día.</p>
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

                    <a href="../vendedor/pedidos_pendientes.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Pedidos Recientes</div>
                        <div class="card-body">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="card-footer" id="kpi-pendientes">
                            <?php echo $total_pendientes; ?> Pendientes
                        </div>
                    </a>

                    <a href="../vendedor/Gestion_platillos.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Gestionar Pedidos</div>
                        <div class="card-body">
                            <i class="fas fa-truck"></i>
                        </div>
                        <div class="card-footer" id="kpi-total-general">
                            <?php echo $total_general; ?> Registrados
                        </div>
                    </a>

                    <a href="../vendedor/Misplatillos.php" class="card" style="text-decoration: none; color: inherit;">
                        <div class="card-header">Productos</div>
                        <div class="card-body">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="card-footer" id="kpi-total-productos">
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

    <script src="../js/notificaciones.js?v=2.3"></script>
</body>

</html>