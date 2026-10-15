<?php
// 1. INCLUIR ARCHIVO DE CONEXIÓN Y SESIÓN
include '../conexion/pantalla_usuario_cliente.php';

// Mostrar mensaje de sesión si existe
$mensaje_perfil = $_SESSION['mensaje_perfil'] ?? '';
unset($_SESSION['mensaje_perfil']);

// --- 2. OBTENER DATOS DE LA TIENDA ---
$id_tienda_actual = 0;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_tienda_actual = (int)$_GET['id'];
}
if ($id_tienda_actual <= 0) {
    die("Error: Tienda no especificada.");
}

// Consulta de la tienda
$sql_tienda = "SELECT nombre_negocio, descripcion, dirección FROM vendedor WHERE idvendedor = ?";
$stmt_tienda = $conn->prepare($sql_tienda);
$stmt_tienda->bind_param("i", $id_tienda_actual);
$stmt_tienda->execute();
$resultado_tienda = $stmt_tienda->get_result();

if ($resultado_tienda->num_rows == 0) {
    die("Error: Tienda no encontrada.");
}
$tienda = $resultado_tienda->fetch_assoc();
$nombre_tienda = htmlspecialchars($tienda['nombre_negocio']);
$descripcion_tienda = htmlspecialchars($tienda['descripcion']);

// --- 3. OBTENER PRODUCTOS CON STOCK ---
// Agregamos 'disponible' a la consulta
$sql_productos = "SELECT idproducto, nombreproducto, descripcion, imagen, precio, disponible 
                  FROM producto 
                  WHERE idvendedor = ? AND disponible > 0"; // Mostramos si hay stock > 0

$stmt_productos = $conn->prepare($sql_productos);
$stmt_productos->bind_param("i", $id_tienda_actual);
$stmt_productos->execute();
$resultado_productos = $stmt_productos->get_result();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda: <?php echo $nombre_tienda; ?> - Snackver</title> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/panel-cliente.css">
    <link rel="stylesheet" href="../css/resumen-cliente.css">
    <link rel="shortcut icon" href="../img/logo - snackver.png" type="image/x-icon">
</head>

<body>
    <header>
        <div class="container header-content">
            <a href="dashboard_cliente.php" class="logo"> <img src="../img/logo - snackver.png" alt="Logo de Snackver"> Snackver
            </a>
            <div class="auth-buttons">
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
                <h1>Tu Cuenta <?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre'); ?></h1>
            </div>

            <div class="dashboard-grid">
                <aside class="dashboard-sidebar">
                    <div class="profile-summary">
                        <h4><?php echo htmlspecialchars($datos_cliente['nombre_cliente'] ?? 'Nombre'); ?></h4>
                        <p><?php echo htmlspecialchars($datos_cliente['apellido_cliente'] ?? 'Apellido'); ?></p>
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
                        
                        <button type="button" onclick="javascript:history.back()" style="display: inline-block; margin-bottom: 20px; cursor: pointer; border: none; background: #eee; padding: 8px 12px; border-radius: 5px;">
                            &larr; Volver a Tiendas
                        </button>

                        <h1><?php echo $nombre_tienda; ?></h1>
                        <p style="color: #666; margin-bottom: 20px;"><?php echo $descripcion_tienda; ?></p>
                        <hr>
                        <br>

                        <h3>Productos Disponibles</h3>

                        <div class="products-grid">
                            <?php
                            if ($resultado_productos->num_rows > 0) {
                                while ($producto = $resultado_productos->fetch_assoc()) {
                                    $imagen_producto = !empty($producto['imagen']) ? "../../" . $producto['imagen'] : "../../img/default-food.png";
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
                            
                            $stmt_tienda->close();
                            $stmt_productos->close();
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
<?php
$conn->close();
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Selecciona TODOS los botones de "Añadir al Carrito"
    const botonesAgregar = document.querySelectorAll('.btn-agregar-carrito');

    botonesAgregar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            
            const idProducto = this.dataset.idproducto;
            const precio = this.dataset.precio;

            this.textContent = 'Agregando...';
            this.disabled = true;

            const formData = new FormData();
            formData.append('idproducto', idProducto);
            formData.append('precio', precio);

            // 5. Envía la petición al servidor (AJAX)
            fetch('agregar_al_carrito.php', { // Funciona porque están en la misma carpeta
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.textContent = '¡Agregado!';
                    // Aquí puedes actualizar un contador en el header si quieres
                } else {
                    this.textContent = 'Error. Intentar de nuevo';
                    this.disabled = false;
                    alert(data.message); 
                }
            })
            .catch(error => {
                this.textContent = 'Error de conexión';
                this.disabled = false;
            });
        });
    });
});
</script>