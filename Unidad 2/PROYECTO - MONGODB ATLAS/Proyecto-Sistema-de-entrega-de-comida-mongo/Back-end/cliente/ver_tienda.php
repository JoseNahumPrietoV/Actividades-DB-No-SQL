<?php
// 1. INCLUIR ARCHIVO DE CONEXIÓN Y SESIÓN
require_once __DIR__ . '/../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de sesión si existe
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

// --- 2. OBTENER DATOS DE LA TIENDA (MONGODB ATLAS) ---
$id_tienda_actual = $_GET['id'] ?? '';
if (empty($id_tienda_actual)) {
    die("Error: Tienda no especificada.");
}

$nombre_tienda = "Tienda";
$descripcion_tienda = "";
$productos = [];

try {
    $mongoDb = MongoConexion::getConexion();
    $colUsuarios  = $mongoDb->selectCollection('usuarios');
    $colProductos = $mongoDb->selectCollection('productos');

    $filtroTienda = [];
    if (is_string($id_tienda_actual) && preg_match('/^[a-f\d]{24}$/i', $id_tienda_actual)) {
        $filtroTienda = ['_id' => new MongoDB\BSON\ObjectId($id_tienda_actual)];
    } else {
        $filtroTienda = ['$or' => [['_id' => (int)$id_tienda_actual], ['_id' => (string)$id_tienda_actual]]];
    }

    $tiendaDoc = $colUsuarios->findOne($filtroTienda, [
        'projection' => [
            'tienda' => 1,
            'nombre' => 1
        ]
    ]);
    if (!$tiendaDoc) {
        die("Error: Tienda no encontrada.");
    }

    $tiendaData = $tiendaDoc['tienda'] ?? [];
    $nombre_tienda = htmlspecialchars($tiendaData['nombre_negocio'] ?? ($tiendaDoc['nombre'] . ' Store'));
    $descripcion_tienda = htmlspecialchars($tiendaData['descripcion'] ?? '');
    $foto_tienda = !empty($tiendaData['foto_negocio']) ? $tiendaData['foto_negocio'] : '';

    // --- 3. OBTENER PRODUCTOS CON STOCK DISPONIBLE ---
    $filtroProd = [
        '$and' => [
            ['$or' => [
                ['id_vendedor' => $tiendaDoc['_id']],
                ['id_vendedor' => (string)$tiendaDoc['_id']],
                ['id_vendedor' => (int)$id_tienda_actual]
            ]],
            ['stock_disponible' => ['$gt' => 0]]
        ]
    ];

    $cursorProd = $colProductos->find($filtroProd, [
        'projection' => [
            'nombreproducto'   => 1,
            'descripcion'      => 1,
            'imagen'           => 1,
            'precio'           => 1,
            'stock_disponible' => 1
        ]
    ]);
    foreach ($cursorProd as $docP) {
        $productos[] = [
            'idproducto'     => (string)$docP['_id'],
            'nombreproducto' => $docP['nombreproducto'] ?? '',
            'descripcion'    => $docP['descripcion'] ?? '',
            'imagen'         => $docP['imagen'] ?? '',
            'precio'         => (float)($docP['precio'] ?? 0),
            'disponible'     => (int)($docP['stock_disponible'] ?? 0)
        ];
    }

} catch (Exception $e) {
    die("Error cargando tienda: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda: <?php echo $nombre_tienda; ?> - Snackver</title> 
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
            <a href="dashboard_cliente.php" class="logo"> <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
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
                <a href="carrito.php" class="btn btn-primary"> <i class="fas fa-shopping-cart"></i> Carrito
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
                        <a href="dashboard_cliente.php"><i class="fas fa-tachometer-alt"></i> Resumen</a>
                        <a href="MisPedidos.php"><i class="fas fa-receipt"></i> Mis Pedidos</a>
                        <a href="BuscarTienda.php" class="active"><i class="fas fa-location-dot"></i> Buscar Tiendas</a>
                        <a href="../cliente/resenas.php"><i class="fas fa-pen-nib"></i> Reseñas</a>
                        <a href="Miperfil.php"><i class="fas fa-user-edit"></i> Mi Perfil</a>
                        <a href="../conexion/cerrar_sesion.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </nav>
                </aside>

                <div class="dashboard-content">
                    <div class="dashboard-card">
                        
                        <div style="margin-bottom: 20px;">
                            <button type="button" onclick="javascript:history.back()" style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: none; background: #eee; padding: 8px 14px; border-radius: 5px; font-weight: 500; font-size: 0.95rem;">
                                &larr; Volver a Tiendas
                            </button>
                        </div>

                        <h3 style="margin-top: 0; margin-bottom: 15px;">Productos Disponibles</h3>

                        <div class="products-grid">
                            <?php
                            if (!empty($productos)) {
                                foreach ($productos as $producto) {
                                    $imgRaw = $producto['imagen'] ?? '';
                                    $imagen_producto = !empty($imgRaw) ? (str_starts_with($imgRaw, 'http') ? $imgRaw : "../../" . $imgRaw) : "../../img/default-food.png";
                                    $nombre_producto = htmlspecialchars($producto['nombreproducto']);
                                    $desc_producto = htmlspecialchars($producto['descripcion']);
                                    $precio_producto = number_format($producto['precio'], 2);
                                    $stock_producto = intval($producto['disponible']); // <--- STOCK
                            ?>
                                    <div class="product-card">
                                        <div class="product-image">
                                            <img src="<?php echo $imagen_producto; ?>" alt="<?php echo $nombre_producto; ?>" onerror="this.src='../../img/default-food.png'">
                                        </div>
                                        <div class="product-info">
                                            <h4><?php echo $nombre_producto; ?></h4>
                                            <p style="font-size: 0.9em; color: #777; margin-bottom: 10px;">
                                                <?php echo substr($desc_producto, 0, 60) . '...'; ?>
                                            </p>
                                            
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                <div class="product-price" style="font-weight: bold; color: #27ae60; font-size: 1.1em;">
                                                    $<?php echo $precio_producto; ?>
                                                </div>
                                                
                                                <span class="stock-badge">
                                                    <i class="fas fa-box-open"></i> <?php echo $stock_producto; ?> disp.
                                                </span>
                                            </div>

                                            <div class="product-actions">
                                                <button class="btn btn-agregar-carrito"
                                                        style="width: 100%; background-color: #3498db; color: white; border: none; padding: 8px; border-radius: 5px; cursor: pointer;"
                                                        data-idproducto="<?php echo $producto['idproducto']; ?>"
                                                        data-precio="<?php echo $producto['precio']; ?>">
                                                    Añadir al Carrito
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                }
                            } else {
                                echo "<p>Esta tienda no tiene productos disponibles en este momento.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const gridProductos = document.querySelector('.products-grid');
    if (!gridProductos) return;

    gridProductos.addEventListener('click', function(e) {
        const boton = e.target.closest('.btn-agregar-carrito');
        if (!boton || boton.disabled) return;

        const idProducto = boton.dataset.idproducto;
        const precio = boton.dataset.precio;

        boton.textContent = 'Agregando...';
        boton.disabled = true;

        const formData = new FormData();
        formData.append('idproducto', idProducto);
        formData.append('precio', precio);

        fetch('agregar_al_carrito.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                boton.textContent = '¡Agregado!';
                setTimeout(() => {
                    boton.textContent = 'Añadir al Carrito';
                    boton.disabled = false;
                }, 1200);
            } else {
                boton.textContent = 'Error. Intentar de nuevo';
                boton.disabled = false;
                alert(data.message);
            }
        })
        .catch(() => {
            boton.textContent = 'Error de conexión';
            boton.disabled = false;
        });
    });
});
</script>
<script src="../js/notificaciones.js?v=2.3"></script>
<script src="../../Front-end/js/tiempo_real.js?v=2.3"></script>