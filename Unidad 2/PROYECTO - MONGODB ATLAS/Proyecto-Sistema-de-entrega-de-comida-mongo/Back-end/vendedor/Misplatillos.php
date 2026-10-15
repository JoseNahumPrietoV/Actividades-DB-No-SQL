<?php
// 1. INCLUIR EL ARCHIVO DE SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_vendedor.php';

// Mostrar mensaje de resultado de la sesión (si existe)
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// Obtenemos el ID del vendedor
$id_vendedor = $_SESSION['id_vendedor'];

// Liberar candado de sesión inmediatamente para permitir recargas y concurrencia fluida
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 2. CONSULTA DE PLATILLOS (MONGODB ATLAS) ---
$productos = [];
try {
    $mongoDb = MongoConexion::getConexion();
    $colProductos = $mongoDb->selectCollection('productos');
    $filtro = [];
    if (is_string($id_vendedor) && preg_match('/^[a-f\d]{24}$/i', $id_vendedor)) {
        $filtro = ['$or' => [
            ['id_vendedor' => new MongoDB\BSON\ObjectId($id_vendedor)],
            ['id_vendedor' => (string)$id_vendedor],
            ['id_vendedor' => (int)$id_vendedor]
        ]];
    } else {
        $filtro = ['$or' => [
            ['id_vendedor' => (int)$id_vendedor],
            ['id_vendedor' => (string)$id_vendedor]
        ]];
    }
    $cursor = $colProductos->find($filtro, [
        'projection' => [
            'nombreproducto'   => 1,
            'descripcion'      => 1,
            'imagen'           => 1,
            'precio'           => 1,
            'stock_disponible' => 1,
            'disponible'       => 1
        ]
    ]);
    foreach ($cursor as $doc) {
        $productos[] = [
            'idproducto'     => (string)$doc['_id'],
            'nombreproducto' => $doc['nombreproducto'] ?? '',
            'descripcion'    => $doc['descripcion'] ?? '',
            'imagen'         => $doc['imagen'] ?? 'img/default-food.png',
            'precio'         => (float)($doc['precio'] ?? 0),
            'disponible'     => (int)($doc['stock_disponible'] ?? ($doc['disponible'] ?? 0))
        ];
    }
} catch (Exception $e) {
    error_log("Error cargando platillos en Misplatillos.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Platillos - Snackver</title>

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
                <h1>Productos</h1>

            </div>

            <div class="filter-section">
                <a href="dashboard_vendedor.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </div>


                <div class="dashboard-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Mis Platillos</h3>
                        <a href="nuevoplatillo.php" class="btn btn-primary" style="text-decoration: none;"><i class="fas fa-plus"></i> Agregar Platillo</a>
                    </div>

                    <div class="products-grid" id="products-grid-container">
                        <?php
                        if (!empty($productos)) {
                            foreach ($productos as $fila) {

                                // Lógica visual para el stock
                                $stock = intval($fila['disponible']);
                                $clase_stock = 'stock-ok';
                                $texto_stock = $stock . ' disponibles';

                                if ($stock == 0) {
                                    $clase_stock = 'stock-out';
                                    $texto_stock = 'Agotado';
                                } elseif ($stock < 5) {
                                    $clase_stock = 'stock-low';
                                    $texto_stock = '¡Solo quedan ' . $stock . '!';
                                }
                        ?>
                                <div class="product-card" id="product-card-<?php echo $fila['idproducto']; ?>">
                                    <div class="product-image">
                                        <?php
                                        $imgSrc = $fila['imagen'] ?? '';
                                        $rutaPlatilloImg = str_starts_with($imgSrc, 'http') ? $imgSrc : "../../" . $imgSrc;
                                        ?>
                                        <img src="<?php echo htmlspecialchars($rutaPlatilloImg); ?>"
                                            alt="<?php echo htmlspecialchars($fila['nombreproducto']); ?>"
                                            decoding="async"
                                            onerror="this.src='../../img/default-food.png'"
                                            style="width: 100%; height: 160px; object-fit: cover; border-radius: 6px 6px 0 0;">
                                    </div>
                                    <div class="product-info">
                                        <h4><?php echo htmlspecialchars($fila['nombreproducto']); ?></h4>

                                        <div style="margin-bottom: 5px;">
                                            <span class="badge-stock <?php echo $clase_stock; ?>">
                                                <i class="fas fa-box"></i> <?php echo $texto_stock; ?>
                                            </span>
                                        </div>

                                        <p><?php echo htmlspecialchars($fila['descripcion']); ?></p>

                                        <div class="product-price">$<?php echo number_format($fila['precio'], 2); ?></div>

                                        <div class="product-actions">
                                            <a href="editar_platillo.php?id=<?php echo $fila['idproducto']; ?>" class="btn btn-primary" style="text-decoration: none;">Editar</a>

                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "<p style='grid-column: 1 / -1; text-align: center;'>No tienes platillos registrados. Haz clic en 'Agregar Platillo' para empezar.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </main>

    <div id="delete-modal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div style="text-align: center; margin-bottom: 12px;">
                <img src="../img/logo - snackver.png" alt="Snackver" style="width: 44px; height: 44px; object-fit: contain;">
            </div>
            <h4>Confirmar Eliminación</h4>
            <p>¿Estás seguro de que quieres eliminar el platillo "<strong id="modal-product-name"></strong>"?</p>
            <p style="color: #e74c3c; font-size: 0.9em;">Esta acción no se puede deshacer.</p>
            <div class="modal-actions">
                <button id="modal-btn-cancel" class="btn btn-secondary">Cancelar</button>
                <button id="modal-btn-confirm" class="btn btn-danger">Sí, Eliminar</button>
            </div>
        </div>
    </div>

    <script src="../js/notificaciones.js?v=2.3"></script>
    <script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>
</body>

</html>